<?php

namespace Tests\Feature;

use App\Models\CommissionScheme;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Ordering\Data\CheckoutDetails;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\CheckoutService;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{order: Order, product: Product}
     */
    private function createUnpaidOrder(int $basePrice = 50000, int $qty = 2): array
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        CommissionScheme::factory()->create([
            'tenant_id' => $tenant->id,
            'commission_rate' => 0.1000,
            'valid_from' => now()->subMonth(),
            'valid_to' => null,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'base_price' => $basePrice,
            'stock_qty' => 10,
            'is_available' => true,
        ]);

        $session = new CustomerSession;
        $session->forceFill([
            'tenant_id' => $tenant->id,
            'session_token_hash' => hash('sha256', 'admin-pay-'.Str::random(16)),
            'status' => 'active',
            'expires_at' => now()->addHours(4),
        ])->save();

        app(CartService::class)->add($session, $product->id, $qty);

        $result = app(CheckoutService::class)->checkout(
            $session,
            (string) Str::uuid(),
            null,
            new CheckoutDetails(CheckoutDetails::DELIVERY, 'Andi', '628123456780', 'Jl. Sudirman No. 5'),
        );

        return [
            'order' => $result->order,
            'product' => $product,
        ];
    }

    public function test_non_admin_cannot_access_admin_payments(): void
    {
        $this->get(route('admin.payments.index'))
            ->assertRedirect(route('login'));

        $customer = User::factory()->create([
            'role' => null,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('admin.payments.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.payments.create'))
            ->assertForbidden();
    }

    public function test_admin_can_create_payment_for_unpaid_order_and_view_detail_with_qris_and_splits(): void
    {
        ['order' => $order] = $this->createUnpaidOrder(50000, 2); // Grand total: 112.000

        $this->actingAs($this->admin)
            ->get(route('admin.payments.create'))
            ->assertOk()
            ->assertSee('#'.$order->order_number);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.payments.store'), [
                'order_id' => $order->id,
            ]);

        $payment = Payment::query()->where('order_id', $order->id)->sole();
        $response->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame(112000, $payment->amount);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(1, PaymentAttempt::query()->where('payment_id', $payment->id)->count());

        $this->actingAs($this->admin)
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee($payment->payment_reference)
            ->assertSee('#'.$order->order_number)
            ->assertSee('NMID •••8821')
            ->assertSee('<svg', false)
            ->assertSee('Rincian Split Pembayaran per Tenant')
            ->assertSee('FAKE-QRIS-');
    }

    public function test_admin_can_list_filter_and_search_payments(): void
    {
        ['order' => $orderPending] = $this->createUnpaidOrder(40000, 1);
        ['order' => $orderPaid] = $this->createUnpaidOrder(60000, 1);

        $payments = app(PaymentService::class);
        $pendingPayment = $payments->initiate($orderPending);
        $paidPayment = $payments->initiate($orderPaid);
        $payments->confirmSandbox($paidPayment);

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee($pendingPayment->payment_reference)
            ->assertSee($paidPayment->payment_reference);

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['status' => 'paid']))
            ->assertOk()
            ->assertSee($paidPayment->payment_reference)
            ->assertDontSee($pendingPayment->payment_reference);

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['search' => $pendingPayment->payment_reference]))
            ->assertOk()
            ->assertSee($pendingPayment->payment_reference)
            ->assertDontSee($paidPayment->payment_reference);
    }

    public function test_admin_can_expire_and_regenerate_qris_in_sandbox(): void
    {
        ['order' => $order] = $this->createUnpaidOrder();
        $payment = app(PaymentService::class)->initiate($order);
        $firstAttempt = PaymentAttempt::query()->sole();

        $this->actingAs($this->admin)
            ->patch(route('admin.payments.expire', $payment))
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame('expired', $firstAttempt->fresh()->status);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.regenerate', $payment))
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame(2, PaymentAttempt::query()->where('payment_id', $payment->id)->count());
        $latest = PaymentAttempt::query()->where('payment_id', $payment->id)->latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertSame('pending', $latest->status);
        $this->assertTrue($latest->expires_at?->isFuture() ?? false);
    }

    public function test_admin_can_simulate_sandbox_payment_and_records_payment_event(): void
    {
        ['order' => $order] = $this->createUnpaidOrder();
        $payment = app(PaymentService::class)->initiate($order);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.simulate-pay', $payment))
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(
            ['new_order'],
            $order->tenantOrders()->withoutGlobalScope('tenant')->pluck('status')->all(),
        );
        $this->assertSame(1, DB::table('payment_events')->where('payment_id', $payment->id)->where('result', 'verified')->count());

        $this->actingAs($this->admin)
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('LUNAS (SETTLEMENT)')
            ->assertSee('SBX-'.$payment->payment_reference);
    }

    public function test_admin_can_cancel_unpaid_payment_and_restores_stock(): void
    {
        ['order' => $order, 'product' => $product] = $this->createUnpaidOrder(50000, 2);
        $payment = app(PaymentService::class)->initiate($order);
        $this->assertSame(8, $product->fresh()->stock_qty);

        $this->actingAs($this->admin)
            ->delete(route('admin.payments.destroy', $payment))
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock_qty);
    }

    public function test_admin_cannot_cancel_or_delete_paid_payment(): void
    {
        ['order' => $order, 'product' => $product] = $this->createUnpaidOrder(50000, 2);
        $payment = app(PaymentService::class)->initiate($order);
        app(PaymentService::class)->confirmSandbox($payment);

        $this->actingAs($this->admin)
            ->from(route('admin.payments.show', $payment))
            ->delete(route('admin.payments.destroy', $payment))
            ->assertRedirect(route('admin.payments.show', $payment))
            ->assertSessionHas('error');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock_qty);
    }
}

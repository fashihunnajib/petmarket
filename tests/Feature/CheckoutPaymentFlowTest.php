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
use App\Modules\Ordering\Data\CheckoutResult;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\CheckoutService;
use App\Modules\Ordering\Services\ResolveTrackedOrder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    /**
     * @return array{result: CheckoutResult, productA: Product, user: User|null}
     */
    private function checkoutMultiTenantCart(?User $user = null, ?CarbonImmutable $scheduledAt = null): array
    {
        $tenantA = Tenant::factory()->create([
            'name' => 'PetShop Alpha',
            'status' => 'active',
            'pre_order_enabled' => true,
            'pre_order_slot_capacity' => 5,
        ]);
        $tenantB = Tenant::factory()->create([
            'name' => 'PetShop Beta',
            'status' => 'active',
            'pre_order_enabled' => true,
            'pre_order_slot_capacity' => 5,
        ]);

        CommissionScheme::factory()->create([
            'tenant_id' => $tenantA->id,
            'commission_rate' => 0.1500,
            'valid_from' => now()->subMonth(),
            'valid_to' => null,
        ]);
        CommissionScheme::factory()->create([
            'tenant_id' => $tenantB->id,
            'commission_rate' => 0.1000,
            'valid_from' => now()->subMonth(),
            'valid_to' => null,
        ]);

        $productA = Product::factory()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Royal Canin 2kg',
            'base_price' => 50000,
            'stock_qty' => 10,
            'is_available' => true,
        ]);
        $productB = Product::factory()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Pasir Kucing 5L',
            'base_price' => 30000,
            'stock_qty' => 5,
            'is_available' => true,
        ]);

        $session = new CustomerSession;
        $session->forceFill([
            'user_id' => $user?->id,
            'tenant_id' => $tenantA->id,
            'session_token_hash' => hash('sha256', 'sess-'.Str::random(16)),
            'status' => 'active',
            'expires_at' => now()->addHours(4),
        ])->save();

        $cart = app(CartService::class);
        $cart->add($session, $productA->id, 2); // 100.000
        $cart->add($session, $productB->id, 1); // 30.000

        $details = $scheduledAt !== null
            ? new CheckoutDetails(CheckoutDetails::PICKUP, 'Raka', '628123456789')
            : new CheckoutDetails(CheckoutDetails::DELIVERY, 'Budi', '628123456789', 'Jl. Melati No. 1');

        $result = app(CheckoutService::class)->checkout(
            $session,
            (string) Str::uuid(),
            $scheduledAt,
            $details,
        );

        return [
            'result' => $result,
            'productA' => $productA,
            'user' => $user,
        ];
    }

    private function bindTrackedOrder(Order $order): void
    {
        $this->app->instance(ResolveTrackedOrder::class, new class($order->id) extends ResolveTrackedOrder
        {
            public function __construct(private int $orderId) {}

            public function current(Request $request): ?Order
            {
                return Order::query()->find($this->orderId);
            }
        });
    }

    public function test_guest_checkout_lands_on_order_status_with_auto_generated_qris_svg(): void
    {
        ['result' => $result] = $this->checkoutMultiTenantCart();
        $order = $result->order;

        // Subtotal 130.000 + Pajak 13.000 + Fee 2.600 = 145.600
        $this->withUnencryptedCookie(ResolveTrackedOrder::COOKIE, (string) $result->trackingToken)
            ->get(route('ordering.status'))
            ->assertOk()
            ->assertSeeLivewire('payments::order-payment')
            ->assertSee('Pembayaran QRIS')
            ->assertSee('#'.$order->order_number)
            ->assertSee('Rp145.600')
            ->assertSee('NMID •••8821')
            ->assertSee('<svg', false)
            ->assertSee('PetShop Alpha')
            ->assertSee('PetShop Beta');

        $payment = Payment::query()->where('order_id', $order->id)->sole();
        $this->assertSame(145600, $payment->amount);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(1, PaymentAttempt::query()->where('payment_id', $payment->id)->count());
    }

    public function test_order_status_returns_404_without_valid_tracking_cookie(): void
    {
        $this->get(route('ordering.status'))->assertNotFound();

        $this->withUnencryptedCookie(ResolveTrackedOrder::COOKIE, 'token-tidak-valid')
            ->get(route('ordering.status'))
            ->assertNotFound();
    }

    public function test_guest_sandbox_payment_marks_regular_order_new_order_and_keeps_preorder_scheduled(): void
    {
        ['result' => $regular] = $this->checkoutMultiTenantCart();
        $this->bindTrackedOrder($regular->order);

        Livewire::test('payments::order-payment', ['orderId' => $regular->order->id])
            ->call('simulatePay')
            ->assertSee('Pembayaran berhasil. Pesanan diteruskan ke toko!')
            ->assertSee('SETTLEMENT');

        $this->assertSame('paid', $regular->order->fresh()->status);
        $this->assertSame(
            ['new_order', 'new_order'],
            $regular->order->tenantOrders()->withoutGlobalScope('tenant')->pluck('status')->all(),
        );
        $this->assertSame(1, DB::table('payment_events')->where('result', 'verified')->count());

        // Uji pre-order: setelah lunas, tenant_orders harus tetap `scheduled` sampai dilepas scheduler
        ['result' => $preOrder] = $this->checkoutMultiTenantCart(
            scheduledAt: CarbonImmutable::now('Asia/Jakarta')->addHour(),
        );
        $this->bindTrackedOrder($preOrder->order);

        Livewire::test('payments::order-payment', ['orderId' => $preOrder->order->id])
            ->call('simulatePay')
            ->assertSee('Pembayaran berhasil. Pesanan diteruskan ke toko!');

        $this->assertSame('paid', $preOrder->order->fresh()->status);
        $this->assertSame(
            ['scheduled', 'scheduled'],
            $preOrder->order->tenantOrders()->withoutGlobalScope('tenant')->pluck('status')->all(),
        );
    }

    public function test_guest_cancels_unpaid_order_and_restores_stock_from_checkout(): void
    {
        ['result' => $result, 'productA' => $productA] = $this->checkoutMultiTenantCart();
        $this->assertSame(8, $productA->fresh()->stock_qty);

        $this->bindTrackedOrder($result->order);

        Livewire::test('payments::order-payment', ['orderId' => $result->order->id])
            ->call('cancel')
            ->assertSee('Pesanan dibatalkan.');

        $this->assertSame('cancelled', $result->order->fresh()->status);
        $this->assertSame(
            ['cancelled', 'cancelled'],
            $result->order->tenantOrders()->withoutGlobalScope('tenant')->pluck('status')->all(),
        );
        $this->assertSame(10, $productA->fresh()->stock_qty);
        $this->assertSame('failed', Payment::query()->where('order_id', $result->order->id)->sole()->status);
        $this->assertSame('expired', PaymentAttempt::query()->sole()->status);
    }

    public function test_guest_regenerates_qris_after_expiry(): void
    {
        ['result' => $result] = $this->checkoutMultiTenantCart();
        $this->bindTrackedOrder($result->order);

        Livewire::test('payments::order-payment', ['orderId' => $result->order->id])
            ->assertSee('<svg', false);

        $firstAttempt = PaymentAttempt::query()->sole();
        $this->travel(16)->minutes();

        Livewire::test('payments::order-payment', ['orderId' => $result->order->id])
            ->assertSee('QRIS kedaluwarsa sebelum dibayar.')
            ->assertSee('Buat QRIS baru')
            ->call('regenerate')
            ->assertSee('Menunggu pembayaran');

        $this->assertSame('expired', $firstAttempt->fresh()->status);
        $this->assertSame(2, PaymentAttempt::query()->count());
        $this->assertSame('pending', PaymentAttempt::query()->latest('id')->first()?->status);
        $this->assertSame('awaiting_payment', $result->order->fresh()->status);
    }

    public function test_logged_in_customer_checkout_appears_in_history_and_blocks_other_users(): void
    {
        $customer = User::factory()->create(['role' => null, 'status' => 'active']);
        $otherCustomer = User::factory()->create(['role' => null, 'status' => 'active']);

        ['result' => $result] = $this->checkoutMultiTenantCart(user: $customer);
        $order = $result->order;

        $this->assertSame($customer->id, $order->user_id);

        $this->actingAs($customer)
            ->get(route('ordering.history'))
            ->assertOk()
            ->assertSee('#'.$order->order_number)
            ->assertSee('Lanjut Bayar QRIS');

        $this->actingAs($customer)
            ->get(route('ordering.status', ['order' => $order->public_id]))
            ->assertOk()
            ->assertSee('Pembayaran QRIS')
            ->assertSee('#'.$order->order_number);

        $this->actingAs($otherCustomer)
            ->get(route('ordering.status', ['order' => $order->public_id]))
            ->assertNotFound();
    }
}

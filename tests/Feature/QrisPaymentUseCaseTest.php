<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\TenantOrder;
use App\Models\User;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Data\PaymentChargeRequest;
use App\Modules\Payments\Data\QrisCharge;
use App\Modules\Payments\Exceptions\GatewayUnavailableException;
use App\Modules\Payments\Gateways\FakeQrisGateway;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class QrisPaymentUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Product $productA;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create();

        $tenantA = Tenant::factory()->create();
        $catA = ProductCategory::factory()->create(['tenant_id' => $tenantA->id]);
        $this->productA = Product::factory()->create([
            'tenant_id' => $tenantA->id,
            'category_id' => $catA->id,
            'base_price' => 50000,
            'stock_qty' => 8, // Anggap sudah terpotong 2 saat checkout dari stok awal 10
        ]);

        $tenantB = Tenant::factory()->create();

        // Subtotal A = 100.000, ongkir 10.000 -> gross 110.000, komisi 10.000, net 90.000
        // Subtotal B = 50.000, ongkir 15.000  -> gross 65.000,  komisi 5.000,  net 45.000
        // Grand Total = 175.000
        $this->order = Order::query()->create([
            'public_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-001',
            'user_id' => $this->customer->id,
            'checkout_key' => (string) Str::uuid(),
            'status' => 'awaiting_payment',
            'subtotal_amount' => 150000,
            'tax_amount' => 0,
            'shipping_cost' => 25000,
            'service_fee_amount' => 0,
            'grand_total_amount' => 175000,
            'shipping_address' => 'Jl. Raya Banyuwangi No. 1',
            'placed_at' => now(),
        ]);

        $toA = new TenantOrder;
        $toA->forceFill([
            'public_id' => (string) Str::uuid(),
            'order_number' => 'TO-TEST-A',
            'order_id' => $this->order->id,
            'tenant_id' => $tenantA->id,
            'user_id' => $this->customer->id,
            'checkout_key' => (string) Str::uuid(),
            'status' => 'pending_payment',
            'commission_rate_snapshot' => 0.1000,
            'subtotal_amount' => 100000,
            'tax_amount' => 0,
            'shipping_cost' => 10000,
            'service_fee_amount' => 0,
            'commission_amount' => 10000,
            'net_amount' => 90000,
            'grand_total_amount' => 110000,
            'shipping_address' => 'Jl. Raya Banyuwangi No. 1',
        ])->save();

        $itemA = new OrderItem;
        $itemA->forceFill([
            'tenant_id' => $tenantA->id,
            'tenant_order_id' => $toA->id,
            'product_id' => $this->productA->id,
            'name_snapshot' => $this->productA->name,
            'type_snapshot' => 'good',
            'unit_price_snapshot' => 50000,
            'quantity' => 2,
            'line_total' => 100000,
        ])->save();

        $toB = new TenantOrder;
        $toB->forceFill([
            'public_id' => (string) Str::uuid(),
            'order_number' => 'TO-TEST-B',
            'order_id' => $this->order->id,
            'tenant_id' => $tenantB->id,
            'user_id' => $this->customer->id,
            'checkout_key' => (string) Str::uuid(),
            'status' => 'pending_payment',
            'commission_rate_snapshot' => 0.1000,
            'subtotal_amount' => 50000,
            'tax_amount' => 0,
            'shipping_cost' => 15000,
            'service_fee_amount' => 0,
            'commission_amount' => 5000,
            'net_amount' => 45000,
            'grand_total_amount' => 65000,
            'shipping_address' => 'Jl. Raya Banyuwangi No. 1',
        ])->save();
    }

    private function spyGateway(bool $unavailable = false): SpyPetMarketQrisGateway
    {
        $spy = new SpyPetMarketQrisGateway($unavailable);
        $this->app->instance(PaymentGateway::class, $spy);

        return $spy;
    }

    public function test_charge_request_carries_splits_including_shipping_and_max_15_min_expiry(): void
    {
        $spy = $this->spyGateway();
        $payment = app(PaymentService::class)->initiate($this->order);

        $request = $spy->requests[0];
        $this->assertCount(2, $request->splits);
        $this->assertSame(175000, array_sum(array_column($request->splits, 'gross')));
        $this->assertSame(175000, $request->amount);
        $this->assertLessThanOrEqual(900, $request->expiresInSeconds);

        $attempt = $payment->latestAttempt;
        $this->assertNotNull($attempt);
        $this->assertStringStartsWith('000201', (string) $attempt->qris_payload);
        $this->assertStringContainsString('5406175000', (string) $attempt->qris_payload);
    }

    public function test_initiate_is_idempotent(): void
    {
        $first = app(PaymentService::class)->initiate($this->order);
        $second = app(PaymentService::class)->initiate($this->order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(1, PaymentAttempt::query()->count());
    }

    public function test_gateway_unavailable_is_retried_three_times_then_shows_disruption_message(): void
    {
        Sleep::fake();
        $spy = $this->spyGateway(unavailable: true);

        Livewire::actingAs($this->customer)
            ->test('payments::order-payment', ['orderId' => $this->order->id])
            ->assertSee('Pembayaran sedang mengalami gangguan. Silakan coba beberapa saat lagi.')
            ->assertSee('Coba lagi');

        $this->assertCount(3, $spy->requests);
        $this->assertSame(0, PaymentAttempt::query()->count());
        $this->assertTrue(AuditLog::query()->where('action', 'gateway_unavailable')->exists());
    }

    public function test_expired_qris_can_be_regenerated(): void
    {
        Livewire::actingAs($this->customer)
            ->test('payments::order-payment', ['orderId' => $this->order->id])
            ->assertSee('NMID •••8821')
            ->assertSee('<svg', false);

        $first = PaymentAttempt::query()->sole();
        $this->travel(16)->minutes();

        Livewire::actingAs($this->customer)
            ->test('payments::order-payment', ['orderId' => $this->order->id])
            ->assertSee('QRIS kedaluwarsa sebelum dibayar.')
            ->assertSee('Buat QRIS baru')
            ->call('regenerate')
            ->assertSee('Menunggu pembayaran');

        $this->assertSame('expired', $first->fresh()->status);
        $this->assertSame(2, PaymentAttempt::query()->count());
    }

    public function test_sandbox_payment_marks_order_paid_and_tenant_orders_new_order(): void
    {
        Livewire::actingAs($this->customer)
            ->test('payments::order-payment', ['orderId' => $this->order->id])
            ->call('simulatePay')
            ->assertSee('Pembayaran berhasil. Pesanan diteruskan ke toko!');

        $this->assertSame('paid', $this->order->fresh()->status);
        $this->assertSame(['new_order', 'new_order'], $this->order->tenantOrders()->withoutGlobalScope('tenant')->pluck('status')->all());
    }

    public function test_customer_cancels_unpaid_order_and_product_stock_is_restored(): void
    {
        Livewire::actingAs($this->customer)
            ->test('payments::order-payment', ['orderId' => $this->order->id])
            ->call('cancel')
            ->assertSee('Pesanan dibatalkan.');

        $this->assertSame('cancelled', $this->order->fresh()->status);
        $this->assertSame(10, $this->productA->fresh()->stock_qty);
        $this->assertSame('failed', Payment::query()->sole()->status);
        $this->assertSame('expired', PaymentAttempt::query()->sole()->status);
    }

    public function test_other_customer_cannot_access_order_payment(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test('payments::order-payment', ['orderId' => $this->order->id])
            ->assertSee('Pesanan tidak ditemukan');
    }
}

final class SpyPetMarketQrisGateway implements PaymentGateway
{
    /** @var list<PaymentChargeRequest> */
    public array $requests = [];

    public function __construct(private bool $unavailable = false) {}

    public function createQrisCharge(PaymentChargeRequest $request): QrisCharge
    {
        $this->requests[] = $request;

        if ($this->unavailable) {
            throw new GatewayUnavailableException('timeout');
        }

        return (new FakeQrisGateway)->createQrisCharge($request);
    }

    public function name(): string
    {
        return 'spy';
    }

    public function isSandbox(): bool
    {
        return true;
    }
}

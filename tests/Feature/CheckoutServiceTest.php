<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Modules\Ordering\Data\CheckoutDetails;
use App\Modules\Ordering\Exceptions\CheckoutException;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    public function test_checkout_creates_parent_order_splits_per_tenant_and_deducts_stock(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'PetShop A', 'status' => 'active']);
        $tenantB = Tenant::factory()->create(['name' => 'PetShop B', 'status' => 'active']);

        $prodA = Product::factory()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Whiskas 1kg',
            'base_price' => 50000,
            'stock_qty' => 10,
            'is_available' => true,
        ]);

        $prodB = Product::factory()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Pasir Kucing 5L',
            'base_price' => 30000,
            'stock_qty' => 5,
            'is_available' => true,
        ]);

        $session = CustomerSession::create([
            'tenant_id' => $tenantA->id,
            'session_token_hash' => hash('sha256', 'token-test-1'),
            'status' => 'active',
            'expires_at' => now()->addHours(2),
        ]);

        $cart = app(CartService::class);
        $cart->add($session, $prodA->id, 2); // 100.000
        $cart->add($session, $prodB->id, 1); // 30.000

        $details = new CheckoutDetails(CheckoutDetails::DELIVERY, 'Budi', '628123456789', 'Jl. Melati No. 1');
        $key = (string) Str::uuid();

        $result = app(CheckoutService::class)->checkout($session, $key, null, $details);

        $this->assertFalse($result->isReplay);
        $this->assertSame(130000, $result->order->subtotal_amount);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('tenant_orders', 2);
        $this->assertDatabaseHas('products', ['id' => $prodA->id, 'stock_qty' => 8]);
        $this->assertDatabaseHas('products', ['id' => $prodB->id, 'stock_qty' => 4]);

        // Replay dengan idempotency key yang sama tidak menggandakan order / stok
        $replay = app(CheckoutService::class)->checkout($session, $key, null, $details);
        $this->assertTrue($replay->isReplay);
        $this->assertSame($result->order->id, $replay->order->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('products', ['id' => $prodA->id, 'stock_qty' => 8]);
    }

    public function test_checkout_rolls_back_atomically_when_stock_is_insufficient(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $prod = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 25000,
            'stock_qty' => 2,
            'is_available' => true,
        ]);

        $session = CustomerSession::create([
            'tenant_id' => $tenant->id,
            'session_token_hash' => hash('sha256', 'token-test-2'),
            'status' => 'active',
            'expires_at' => now()->addHours(2),
        ]);

        app(CartService::class)->add($session, $prod->id, 2);

        // Stok berubah di DB sebelum checkout ditekan
        $prod->update(['stock_qty' => 1]);

        $this->expectException(CheckoutException::class);

        try {
            app(CheckoutService::class)->checkout(
                $session,
                (string) Str::uuid(),
                null,
                new CheckoutDetails(CheckoutDetails::DELIVERY, 'Siti', '628123456780', 'Jl. Mawar 2')
            );
        } finally {
            $this->assertSame(0, Order::count());
            $this->assertDatabaseHas('products', ['id' => $prod->id, 'stock_qty' => 1]);
        }
    }
}

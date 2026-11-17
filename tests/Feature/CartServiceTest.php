<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Models\Product;
use App\Models\Tenant;
use App\Modules\Ordering\Exceptions\CartException;
use App\Modules\Ordering\Services\CartService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    private function makeCustomerSession(): CustomerSession
    {
        $session = new CustomerSession;
        $session->forceFill([
            'session_token_hash' => hash('sha256', 'token-'.uniqid('', true)),
            'status' => 'active',
            'expires_at' => now()->addHours(4),
        ])->save();

        return $session;
    }

    protected function tearDown(): void
    {
        foreach (CustomerSession::query()->pluck('id') as $id) {
            Redis::del('cart:'.$id);
        }
        parent::tearDown();
    }

    public function test_add_stores_line_in_redis_and_merges_identical_config(): void
    {
        $product = Product::factory()->create(['base_price' => 15000, 'stock_qty' => 50, 'is_available' => true]);
        $session = $this->makeCustomerSession();

        $this->cart()->add($session, $product->id, 2);
        $this->cart()->add($session, $product->id, 3);

        $this->assertSame(1, Redis::exists('cart:'.$session->id));
        $view = $this->cart()->view($session);
        $this->assertCount(1, $view->lines);
        $this->assertSame(5, $view->lines[0]->quantity);
        $this->assertSame(75000, $view->subtotal);
    }

    public function test_add_accepts_product_from_another_tenant_when_context_is_active(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);

        $product = Product::factory()->create([
            'tenant_id' => $tenantB->id,
            'base_price' => 20000,
            'stock_qty' => 10,
            'is_available' => true,
        ]);

        $session = $this->makeCustomerSession();

        app(TenantContext::class)->set($tenantA);

        $this->cart()->add($session, $product->id, 1);

        $view = $this->cart()->view($session);

        $this->assertCount(1, $view->lines);
        $this->assertSame($tenantB->id, $view->lines[0]->tenantId);
        $this->assertTrue($view->lines[0]->available);
    }

    public function test_view_displays_cross_tenant_product_when_context_is_active(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);

        $product = Product::factory()->create([
            'tenant_id' => $tenantB->id,
            'base_price' => 25000,
            'stock_qty' => 10,
            'is_available' => true,
        ]);

        $session = $this->makeCustomerSession();

        app(TenantContext::class)->clear();

        $this->cart()->add($session, $product->id, 2);

        app(TenantContext::class)->set($tenantA);

        $view = $this->cart()->view($session);

        $this->assertCount(1, $view->lines);
        $this->assertSame($tenantB->id, $view->lines[0]->tenantId);
        $this->assertTrue($view->lines[0]->available);
        $this->assertSame(50000, $view->subtotal);
    }

    public function test_add_rejects_product_from_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'suspended']);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'is_available' => true,
            'stock_qty' => 10,
        ]);

        $session = $this->makeCustomerSession();

        $this->expectException(CartException::class);

        $this->cart()->add($session, $product->id, 1);
    }
}

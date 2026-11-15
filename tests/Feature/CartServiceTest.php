<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Models\Product;
use App\Modules\Ordering\Services\CartService;
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
}

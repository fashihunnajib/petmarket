<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Models\Product;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;
use Tests\TestCase;

class CartLivewireTest extends TestCase
{
    use RefreshDatabase;

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

    private function bindSession(?CustomerSession $session): void
    {
        $this->app->instance(ResolveCustomerSession::class, new class($session) extends ResolveCustomerSession
        {
            public function __construct(private ?CustomerSession $stub) {}

            public function current(Request $request): ?CustomerSession
            {
                return $this->stub;
            }
        });
    }

    protected function tearDown(): void
    {
        foreach (CustomerSession::query()->pluck('id') as $id) {
            Redis::del('cart:'.$id);
        }
        parent::tearDown();
    }

    public function test_cart_without_session_shows_empty_state_and_add_is_noop(): void
    {
        $this->bindSession(null);

        Livewire::test('ordering::cart', ['canteenSlug' => 'test-canteen'])
            ->call('add', 999)
            ->assertSee('Belum ada sesi');
    }

    public function test_add_event_adds_line_and_renders_subtotal(): void
    {
        $product = Product::factory()->create(['base_price' => 15000, 'stock_qty' => 5, 'is_available' => true]);
        $session = $this->makeCustomerSession();
        $this->bindSession($session);

        Livewire::test('ordering::cart', ['canteenSlug' => 'test-canteen'])
            ->call('add', $product->id)
            ->assertSee($product->name)
            ->assertSee('15.000');

        $this->assertSame(1, Redis::exists('cart:'.$session->id));
    }
}

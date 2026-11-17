<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Models\User;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerSessionLoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuestSession(): array
    {
        $plainToken = Str::random(32);

        $session = new CustomerSession;
        $session->forceFill([
            'session_token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
            'expires_at' => now()->addHours(4),
        ])->save();

        return [$session, $plainToken];
    }

    public function test_customer_login_links_guest_session_to_customer_account(): void
    {
        $user = User::factory()->create([
            'role' => null,
            'status' => 'active',
        ]);

        [$session, $plainToken] = $this->makeGuestSession();

        $response = $this->withCookie('session_token', $plainToken)
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $this->assertSame(
            $user->id,
            $session->fresh()->user_id
        );
    }

    public function test_admin_login_does_not_link_guest_session(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        [$session, $plainToken] = $this->makeGuestSession();

        $this->withCookie('session_token', $plainToken)
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNull($session->fresh()->user_id);
    }

    public function test_tenant_login_does_not_link_guest_session(): void
    {
        $user = User::factory()->create([
            'role' => 'tenant',
            'status' => 'active',
        ]);

        [$session, $plainToken] = $this->makeGuestSession();

        $this->withCookie('session_token', $plainToken)
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNull($session->fresh()->user_id);
    }

    public function test_customer_login_preserves_guest_cart_session(): void
    {
        $user = User::factory()->create([
            'role' => null,
            'status' => 'active',
        ]);

        [$session, $plainToken] = $this->makeGuestSession();

        // Simpan contoh data keranjang guest ke Redis.
        $cartKey = 'cart:'.$session->id;

        $cartData = json_encode([
            'items' => [
                'product-1' => [
                    'product_id' => 1,
                    'quantity' => 2,
                ],
            ],
        ]);

        Redis::set($cartKey, $cartData);

        // Customer login menggunakan cookie sesi guest.
        $this->withCookie('session_token', $plainToken)
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        // Pastikan sesi yang sama sudah terhubung ke customer.
        $this->assertSame(
            $user->id,
            $session->fresh()->user_id
        );

        // Pastikan resolver masih menemukan sesi yang sama.
        $request = Request::create('/');
        $request->cookies->set('session_token', $plainToken);

        $resolvedSession = app(
            ResolveCustomerSession::class
        )->current($request);

        $this->assertNotNull($resolvedSession);
        $this->assertSame($session->id, $resolvedSession->id);

        // Pastikan data keranjang Redis tetap tersedia.
        $this->assertSame($cartData, Redis::get($cartKey));

        Redis::del($cartKey);
    }
}

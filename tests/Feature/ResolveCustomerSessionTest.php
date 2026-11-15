<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResolveCustomerSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(string $status = 'active', bool $expired = false): array
    {
        $plainToken = Str::random(32);
        $hash = hash('sha256', $plainToken);

        $session = new CustomerSession();
        $session->forceFill([
            'session_token_hash' => $hash,
            'status' => $status,
            'expires_at' => $expired ? now()->subMinute() : now()->addHours(4),
        ])->save();

        return [$session, $plainToken];
    }

    public function test_resolves_active_session_from_plain_token(): void
    {
        [$session, $plain] = $this->makeSession();
        $resolved = app(ResolveCustomerSession::class)->fromToken($plain);

        $this->assertNotNull($resolved);
        $this->assertSame($session->id, $resolved->id);
    }

    public function test_rejects_wrong_expired_or_closed_token(): void
    {
        $resolver = app(ResolveCustomerSession::class);

        $this->assertNull($resolver->fromToken('token-tidak-ada'));

        [, $expiredPlain] = $this->makeSession(expired: true);
        $this->assertNull($resolver->fromToken($expiredPlain));

        [, $closedPlain] = $this->makeSession(status: 'closed');
        $this->assertNull($resolver->fromToken($closedPlain));
    }
}
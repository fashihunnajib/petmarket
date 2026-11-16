<?php

namespace App\Modules\Ordering\Services;

use App\Models\CustomerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ResolveCustomerSession
{
    public function current(Request $request): ?CustomerSession
    {
        $token = $request->cookie('session_token') ?? $request->bearerToken();
        if (! is_string($token) || $token === '') {
            return null;
        }

        return $this->fromToken($token);
    }

    public function resolveOrCreate(Request $request): CustomerSession
    {
        $existing = $this->current($request);
        if ($existing !== null) {
            return $existing;
        }

        $plainToken = Str::random(32);
        $session = new CustomerSession;
        $session->forceFill([
            'session_token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
            'expires_at' => now()->addHours(4),
        ])->save();

        Cookie::queue('session_token', $plainToken, 240);
        $request->cookies->set('session_token', $plainToken);

        return $session;
    }

    public function fromToken(string $plainToken): ?CustomerSession
    {
        $hash = hash('sha256', $plainToken);

        return CustomerSession::query()
            ->where('session_token_hash', $hash)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }
}

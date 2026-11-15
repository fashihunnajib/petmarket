<?php

namespace App\Modules\Ordering\Services;

use App\Models\CustomerSession;
use Illuminate\Http\Request;

class ResolveCustomerSession
{
    public function current(Request $request): ?CustomerSession
    {
        $token = $request->cookie('session_token') ?? $request->bearerToken();
        if (!$token) {
            return null;
        }

        return $this->fromToken($token);
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
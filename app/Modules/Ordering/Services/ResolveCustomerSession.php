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
        if (is_string($token) && $token !== '') {
            $session = $this->fromToken($token);
            if ($session !== null) {
                return $session;
            }
        }

        $sessionId = $request->hasSession()
            ? $request->session()->get('customer_session_id')
            : session('customer_session_id');

        if ($sessionId) {
            return CustomerSession::query()
                ->whereKey($sessionId)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();
        }

        return null;
    }

    public function resolveOrCreate(Request $request): CustomerSession
    {
        $existing = $this->current($request);
        if ($existing !== null) {
            if ($request->hasSession()) {
                $request->session()->put('customer_session_id', $existing->id);
            }

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

        if ($request->hasSession()) {
            $request->session()->put('customer_session_id', $session->id);
        } else {
            session(['customer_session_id' => $session->id]);
        }

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

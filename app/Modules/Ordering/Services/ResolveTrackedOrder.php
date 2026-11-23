<?php

namespace App\Modules\Ordering\Services;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Menyelesaikan Order secara aman dari cookie pelacakan opaque (`order_tracking`)
 * atau kepemilikan akun pelanggan yang login (`orders.user_id`).
 *
 * Digunakan bersama oleh halaman status pesanan (Ordering) dan komponen pembayaran QRIS (Payments)
 * agar mendukung alur guest checkout maupun pelanggan login tanpa celah IDOR/enumeration.
 */
class ResolveTrackedOrder
{
    public const COOKIE = 'order_tracking';

    public function current(Request $request): ?Order
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && $token !== '' ? $this->fromToken($token) : null;
    }

    public function fromToken(string $plainToken): ?Order
    {
        return Order::query()
            ->where('tracking_token_hash', hash('sha256', $plainToken))
            ->first();
    }

    public function resolveByPublicId(Request $request, string $publicId): ?Order
    {
        $tracked = $this->current($request);
        if ($tracked !== null && $tracked->public_id === $publicId) {
            return $tracked;
        }

        $authId = auth()->id();
        if (is_numeric($authId)) {
            return Order::query()
                ->where('public_id', $publicId)
                ->where('user_id', (int) $authId)
                ->first();
        }

        return null;
    }

    public function resolveForRequest(Request $request, ?int $orderId = null): ?Order
    {
        $tracked = $this->current($request);
        if ($tracked !== null && ($orderId === null || $tracked->id === $orderId)) {
            return $tracked;
        }

        $authId = auth()->id();
        if ($orderId !== null && is_numeric($authId)) {
            return Order::query()
                ->whereKey($orderId)
                ->where('user_id', (int) $authId)
                ->first();
        }

        return null;
    }
}

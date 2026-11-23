<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ordering\Services\ResolveTrackedOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Halaman status & pembayaran pesanan pelanggan.
 * Order dikenali lewat cookie pelacakan (`order_tracking`) atau akun pemilik (`orders.user_id`).
 * Tanpa/salah akses -> 404 generik (anti-enumeration).
 */
final class OrderStatusController extends Controller
{
    public function __invoke(Request $request, ResolveTrackedOrder $resolver, ?string $order = null): View
    {
        $resolvedOrder = is_string($order) && $order !== ''
            ? $resolver->resolveByPublicId($request, $order)
            : $resolver->current($request);

        if ($resolvedOrder === null) {
            abort(404);
        }

        $resolvedOrder->load([
            'tenantOrders.tenant',
            'tenantOrders.items',
            'payment.latestAttempt',
        ]);

        /** @var view-string $viewName */
        $viewName = 'ordering::order';

        return view($viewName, ['order' => $resolvedOrder]);
    }
}

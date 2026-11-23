<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\Ordering\Services\ResolveTrackedOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

final class CustomerOrderHistoryController extends Controller
{
    public function __invoke(Request $request, ResolveTrackedOrder $resolver): View
    {
        $trackedOrder = $resolver->current($request);
        $authId = auth()->id();
        $userId = is_numeric($authId) ? (int) $authId : null;

        /** @var Collection<int, Order> $orders */
        $orders = new Collection;

        if ($userId !== null || $trackedOrder !== null) {
            $orders = Order::query()
                ->where(static function ($query) use ($userId, $trackedOrder): void {
                    if ($userId !== null) {
                        $query->where('user_id', $userId);
                    }

                    if ($trackedOrder !== null) {
                        if ($userId !== null) {
                            $query->orWhere('id', $trackedOrder->id);
                        } else {
                            $query->where('id', $trackedOrder->id);
                        }
                    }
                })
                ->with([
                    'payment.latestAttempt',
                    'tenantOrders.tenant',
                ])
                ->latest('id')
                ->limit(25)
                ->get();
        }

        /** @var view-string $viewName */
        $viewName = 'ordering::history';

        return view($viewName, [
            'orders' => $orders,
            'trackedOrderId' => $trackedOrder?->id,
        ]);
    }
}

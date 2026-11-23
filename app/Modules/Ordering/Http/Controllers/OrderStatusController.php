<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class OrderStatusController extends Controller
{
    public function __invoke(Request $request): View
    {
        $token = $request->cookie('order_tracking');
        abort_if(! is_string($token) || $token === '', 404);

        /** @var Order|null $order */
        $order = Order::query()
            ->where('tracking_token_hash', hash('sha256', $token))
            ->with([
                'tenantOrders.tenant',
                'tenantOrders.items',
            ])
            ->first();

        abort_if($order === null, 404);

        /** @var view-string $viewName */
        $viewName = 'ordering::order';

        return view($viewName, ['order' => $order]);
    }
}

<?php

namespace App\Modules\Ordering\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TenantOrder;
use App\Modules\Admin\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class CancelUnpaidOrder
{
    public function __construct(private AuditLogger $audit) {}

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $lockedOrder->isAwaitingPayment()) {
                return;
            }

            $lockedOrder->forceFill(['status' => 'cancelled'])->save();

            $tenantOrders = TenantOrder::query()
                ->withoutGlobalScope('tenant')
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->get();

            foreach ($tenantOrders as $tenantOrder) {
                $tenantOrder->forceFill(['status' => 'cancelled'])->save();

                $items = OrderItem::query()
                    ->withoutGlobalScope('tenant')
                    ->where('tenant_order_id', $tenantOrder->id)
                    ->get();

                foreach ($items as $item) {
                    Product::query()
                        ->withoutGlobalScope('tenant')
                        ->where('id', $item->product_id)
                        ->where('tenant_id', $tenantOrder->tenant_id)
                        ->increment('stock_qty', (int) $item->quantity);

                    DB::table('product_stock_movements')->insertOrIgnore([
                        'tenant_id' => $tenantOrder->tenant_id,
                        'product_id' => $item->product_id,
                        'order_item_id' => $item->id,
                        'idempotency_key' => 'cancel-order:'.$lockedOrder->id.':item:'.$item->id,
                        'type' => 'restock',
                        'quantity_delta' => (int) $item->quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->audit->record('order', $lockedOrder->id, 'cancelled_unpaid', null, [
                'order_number' => $lockedOrder->order_number,
            ]);
        });
    }
}

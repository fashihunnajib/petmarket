<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantOrderController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $rawStatus = $request->query('status', 'all');
        $status = is_string($rawStatus) ? $rawStatus : 'all';

        $rawSearch = $request->query('search', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $counts = [
            'all' => TenantOrder::where('tenant_id', $tenant->id)->count(),
            'pending_payment' => TenantOrder::where('tenant_id', $tenant->id)->whereIn('status', ['pending', 'pending_payment'])->count(),
            'scheduled' => TenantOrder::where('tenant_id', $tenant->id)->where('status', 'scheduled')->count(),
            'new_order' => TenantOrder::where('tenant_id', $tenant->id)->where('status', 'new_order')->count(),
            'processing' => TenantOrder::where('tenant_id', $tenant->id)->where('status', 'processing')->count(),
            'in_transit' => TenantOrder::where('tenant_id', $tenant->id)->where('status', 'in_transit')->count(),
            'completed' => TenantOrder::where('tenant_id', $tenant->id)->where('status', 'completed')->count(),
            'cancelled' => TenantOrder::where('tenant_id', $tenant->id)->whereIn('status', ['cancelled', 'expired'])->count(),
        ];

        $orders = TenantOrder::where('tenant_id', $tenant->id)
            ->when($status !== 'all', function ($query) use ($status): void {
                if ($status === 'cancelled') {
                    $query->whereIn('status', ['cancelled', 'expired']);
                } elseif ($status === 'pending_payment') {
                    $query->whereIn('status', ['pending', 'pending_payment']);
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->with(['user', 'items', 'order.payment'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('ordering::index', compact('tenant', 'orders', 'status', 'search', 'counts'));
    }

    public function show(Tenant $tenant, TenantOrder $order): View
    {
        abort_if((int) $order->tenant_id !== (int) $tenant->id, 403);

        $order->load(['user', 'items.product', 'delivery', 'order.payment']);

        return view('ordering::show', compact('tenant', 'order'));
    }

    public function updateStatus(Request $request, Tenant $tenant, TenantOrder $order): RedirectResponse
    {
        abort_if((int) $order->tenant_id !== (int) $tenant->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['processing', 'in_transit', 'completed', 'cancelled'])],
        ]);

        $newStatus = (string) $validated['status'];
        $previousStatus = (string) $order->status;

        if (in_array($previousStatus, ['completed', 'cancelled', 'expired'], true)) {
            return redirect()
                ->back()
                ->with('error', 'Pesanan yang sudah selesai atau dibatalkan tidak dapat diubah statusnya.');
        }

        $order->loadMissing('items');

        DB::transaction(function () use ($tenant, $order, $previousStatus, $newStatus): void {
            $order->update(['status' => $newStatus]);

            // 1. Potong stok produk saat pesanan diproses/dikirim/selesai (idempoten via product_stock_movements)
            if (in_array($newStatus, ['processing', 'in_transit', 'completed'], true) && Schema::hasTable('product_stock_movements')) {
                foreach ($order->items as $item) {
                    $saleKey = 'order_item_'.$item->id.'_sale';
                    $alreadyDeducted = DB::table('product_stock_movements')
                        ->where('idempotency_key', $saleKey)
                        ->exists();

                    if (! $alreadyDeducted) {
                        $qty = (int) $item->quantity;

                        DB::table('product_stock_movements')->insert([
                            'tenant_id' => $tenant->id,
                            'product_id' => $item->product_id,
                            'order_item_id' => $item->id,
                            'idempotency_key' => $saleKey,
                            'type' => 'sale',
                            'quantity_delta' => -$qty,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $currentStock = (int) DB::table('products')
                            ->where('tenant_id', $tenant->id)
                            ->where('id', $item->product_id)
                            ->value('stock_qty');

                        DB::table('products')
                            ->where('tenant_id', $tenant->id)
                            ->where('id', $item->product_id)
                            ->update([
                                'stock_qty' => max(0, $currentStock - $qty),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            // 2. Kembalikan stok jika pesanan dibatalkan setelah stok terpotong
            if ($newStatus === 'cancelled' && Schema::hasTable('product_stock_movements')) {
                foreach ($order->items as $item) {
                    $saleKey = 'order_item_'.$item->id.'_sale';
                    $restockKey = 'order_item_'.$item->id.'_restock';

                    $wasDeducted = DB::table('product_stock_movements')
                        ->where('idempotency_key', $saleKey)
                        ->exists();

                    $alreadyRestocked = DB::table('product_stock_movements')
                        ->where('idempotency_key', $restockKey)
                        ->exists();

                    if ($wasDeducted && ! $alreadyRestocked) {
                        $qty = (int) $item->quantity;

                        DB::table('product_stock_movements')->insert([
                            'tenant_id' => $tenant->id,
                            'product_id' => $item->product_id,
                            'order_item_id' => $item->id,
                            'idempotency_key' => $restockKey,
                            'type' => 'restock',
                            'quantity_delta' => $qty,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('products')
                            ->where('tenant_id', $tenant->id)
                            ->where('id', $item->product_id)
                            ->increment('stock_qty', $qty, ['updated_at' => now()]);
                    }
                }
            }

            // 3. Cairkan pendapatan bersih (net_amount) ke saldo toko saat pesanan selesai
            if ($previousStatus !== 'completed' && $newStatus === 'completed' && Schema::hasTable('tenant_balances')) {
                $balance = DB::table('tenant_balances')->where('tenant_id', $tenant->id)->first();
                $netAmount = (int) $order->net_amount;

                if ($balance !== null) {
                    $currentHeld = (int) ($balance->held_amount ?? 0);
                    $releaseFromHeld = min($currentHeld, $netAmount);

                    DB::table('tenant_balances')
                        ->where('tenant_id', $tenant->id)
                        ->update([
                            'available_amount' => (int) ($balance->available_amount ?? 0) + $netAmount,
                            'held_amount' => max(0, $currentHeld - $releaseFromHeld),
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('tenant_balances')->insert([
                        'tenant_id' => $tenant->id,
                        'available_amount' => $netAmount,
                        'held_amount' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->back()
            ->with('status', 'Status pesanan berhasil diperbarui.');
    }
}

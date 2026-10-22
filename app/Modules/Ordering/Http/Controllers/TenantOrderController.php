<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $tenantId = DB::table('user_tenant_roles')
            ->where('user_id', auth()->id())
            ->value('tenant_id') ?? Tenant::first()?->id;

        $orders = TenantOrder::where('tenant_id', $tenantId)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['order.user', 'items.product'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('ordering::index', compact('orders', 'status'));
    }

    public function show(TenantOrder $order): View
    {
        $tenantId = DB::table('user_tenant_roles')
            ->where('user_id', auth()->id())
            ->value('tenant_id') ?? Tenant::first()?->id;

        abort_if($order->tenant_id !== $tenantId, 403);

        $order->load(['order.user', 'items.product']);

        return view('ordering::show', compact('order'));
    }

    public function updateStatus(Request $request, TenantOrder $order): RedirectResponse
    {
        $tenantId = DB::table('user_tenant_roles')
            ->where('user_id', auth()->id())
            ->value('tenant_id') ?? Tenant::first()?->id;

        abort_if($order->tenant_id !== $tenantId, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:processing,shipped,completed,cancelled'],
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->back()->with('status', 'Status pesanan berhasil diperbarui!');
    }
}

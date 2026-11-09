<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Tenant $tenant): View
    {
        // 1. Total Pesanan
        $totalOrders = DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->count();

        // 2. Pendapatan Hari Ini (Pesanan Selesai)
        $incomeToday = DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereDate('created_at', now()->today())
            ->sum(DB::raw('grand_total_amount - commission_amount'));

        // 3. Saldo Toko (Kolom available_amount)
        $storeBalance = DB::table('tenant_balances')
            ->where('tenant_id', $tenant->id)
            ->value('available_amount') ?? 0;

        // 4. Produk Aktif (Kolom is_available)
        $activeProductsCount = DB::table('products')
            ->where('tenant_id', $tenant->id)
            ->where('is_available', true)
            ->count();

        // 5. Pesanan Terbaru
        $recentOrders = DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get();

        return view('tenant.dashboard', compact(
            'tenant',
            'totalOrders',
            'incomeToday',
            'storeBalance',
            'activeProductsCount',
            'recentOrders'
        ));
    }
}

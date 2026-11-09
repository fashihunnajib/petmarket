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
        // 1. Total seluruh pesanan milik tenant
        $totalOrders = DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->count();

        // 2. Pendapatan hari ini (sum mengembalikan numeric, tidak perlu ?? 0)
        $incomeToday = (float) DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->whereDate('created_at', now()->today())
            ->sum('grand_total_amount');

        // 3. Saldo Toko
        $rawBalance = DB::table('tenant_balances')
            ->where('tenant_id', $tenant->id)
            ->value('available_amount');

        $storeBalance = $rawBalance !== null
            ? (float) $rawBalance
            : (float) DB::table('tenant_orders')->where('tenant_id', $tenant->id)->sum('grand_total_amount');

        $tenantBalance = $storeBalance;

        // 4. Total produk aktif
        $activeProductsCount = DB::table('products')
            ->where('tenant_id', $tenant->id)
            ->where('is_available', true)
            ->count();

        // 5. Data 7 Hari Terakhir untuk Grafik Pendapatan
        $chartLabels = [];
        $chartData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartLabels[] = $date->format('d M');

            $dailyTotal = (float) DB::table('tenant_orders')
                ->where('tenant_id', $tenant->id)
                ->whereDate('created_at', $date->toDateString())
                ->sum('grand_total_amount');

            $chartData[] = $dailyTotal;
        }

        // 6. 5 pesanan terbaru
        $recentOrders = DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('tenant.dashboard', compact(
            'tenant',
            'totalOrders',
            'incomeToday',
            'storeBalance',
            'tenantBalance',
            'activeProductsCount',
            'chartLabels',
            'chartData',
            'recentOrders'
        ));
    }
}

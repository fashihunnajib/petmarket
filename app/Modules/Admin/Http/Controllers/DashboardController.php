<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        // 1. Ambil statistik Tenant
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();

        // 2. Cek ketersediaan tabel tenant_orders
        $hasOrdersTable = Schema::hasTable('tenant_orders');

        $platformCommission = 0;
        $todayTransactions = 0;
        $pendingOrders = 0;

        $chartLabels = [];
        $chartTransactions = [];
        $chartCommissions = [];

        if ($hasOrdersTable) {
            // Total Komisi dari pesanan yang sudah completed
            $platformCommission = (float) DB::table('tenant_orders')
                ->where('status', 'completed')
                ->sum('commission_amount');

            // Total Transaksi hari ini
            $todayTransactions = DB::table('tenant_orders')
                ->whereDate('created_at', Carbon::today())
                ->count();

            // Total Pesanan Menunggu Pembayaran/Proses
            $pendingOrders = DB::table('tenant_orders')
                ->whereIn('status', ['pending_payment', 'new_order'])
                ->count();

            // Data Grafik 30 Hari Terakhir
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $dailyStats = DB::table('tenant_orders')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, COUNT(id) as total_trx, SUM(commission_amount) as total_commission')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $label = Carbon::now()->subDays($i)->format('d M');

                $chartLabels[] = $label;
                $chartTransactions[] = $dailyStats[$date]->total_trx ?? 0;
                $chartCommissions[] = (float) ($dailyStats[$date]->total_commission ?? 0);
            }
        } else {
            for ($i = 29; $i >= 0; $i--) {
                $chartLabels[] = Carbon::now()->subDays($i)->format('d M');
                $chartTransactions[] = 0;
                $chartCommissions[] = 0;
            }
        }

        return view('admin.dashboard', compact(
            'platformCommission',
            'totalTenants',
            'activeTenants',
            'todayTransactions',
            'pendingOrders',
            'chartLabels',
            'chartTransactions',
            'chartCommissions'
        ));
    }
}

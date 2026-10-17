<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantOrder;
use Illuminate\Contracts\View\View;

class DashboardController
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalTenants' => Tenant::count(),
            'activeTenants' => Tenant::where('status', 'active')->count(),
            'totalProducts' => Product::count(),
            'availableProducts' => Product::where('is_available', true)->count(),
            'totalOrders' => TenantOrder::count(),
        ]);
    }
}

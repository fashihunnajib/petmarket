<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TenantController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $tenants = Tenant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
            'totalTenants' => Tenant::count(),
            'activeTenants' => Tenant::where('status', 'active')->count(),
            'pendingTenants' => Tenant::where('status', 'pending')->count(),
        ]);
    }
}

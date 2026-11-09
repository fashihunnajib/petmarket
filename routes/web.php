<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user?->hasRole('tenant')) {
            // Cari tenant_id milik user dari tabel user_tenant_roles
            $tenantId = DB::table('user_tenant_roles')
                ->where('user_id', $user->id)
                ->value('tenant_id');

            /** @var Tenant|null $tenant */
            // Ambil data tenant, atau fallback ke tenant pertama untuk testing
            $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

            if ($tenant) {
                return redirect()->route('tenant.dashboard', [
                    'tenant' => $tenant->slug ?? $tenant->id,
                ]);
            }
        }

        if ($user?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
});
// Baris updateStatus sudah dipindahkan ke rute PortalRoutes::admin

require __DIR__.'/settings.php';

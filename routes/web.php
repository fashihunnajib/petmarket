<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Rute Beli Langsung (Checkout Satuan via CartService)

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if (! $user) {
            return view('dashboard');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        $tenantId = DB::table('user_tenant_roles')
            ->where('user_id', $user->id)
            ->value('tenant_id');

        if ($user->hasRole('tenant') || $tenantId) {
            /** @var Tenant|null $tenant */
            $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

            if ($tenant) {
                return redirect()->route('tenant.dashboard', [
                    'tenant' => $tenant->slug ?? $tenant->id,
                ]);
            }
        }

        return view('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';

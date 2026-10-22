<?php

use App\Modules\Admin\Http\Controllers\SettingController;
use App\Modules\Admin\Http\Controllers\TenantController;
use App\Modules\Admin\Http\Controllers\WithdrawalController;
use App\Support\Routing\PortalRoutes;
use Illuminate\Support\Facades\Route;

PortalRoutes::admin(function (): void {
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');

    // Rute detail
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');

    // Rute untuk fitur Edit
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');

    Route::post('/tenants/{tenant}/status', [TenantController::class, 'updateStatus'])->name('tenants.status');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

    // Rute Verifikasi Dana (Withdrawals)
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

    // Rute Pengaturan
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

});

<?php

use App\Modules\Ordering\Http\Controllers\TenantOrderController;
use App\Support\Routing\PortalRoutes;
use Illuminate\Support\Facades\Route;

PortalRoutes::tenant(function (): void {
    Route::get('/orders', [TenantOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [TenantOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [TenantOrderController::class, 'updateStatus'])->name('orders.update-status');
});

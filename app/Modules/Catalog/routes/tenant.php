<?php

use App\Modules\Catalog\Http\Controllers\TenantProductController;
use App\Support\Routing\PortalRoutes;
use Illuminate\Support\Facades\Route;

/**
 * Route portal tenant milik modul Catalog. {product} ter-scope di bawah {tenant} (scopeBindings
 * dari PortalRoutes) dan difilter global scope TenantContext.
 */
PortalRoutes::tenant(function (): void {
    Route::get('/products', [TenantProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [TenantProductController::class, 'show'])->name('products.show');
    Route::patch('/products/{product}', [TenantProductController::class, 'update'])->name('products.update');
});

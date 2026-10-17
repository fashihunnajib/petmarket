<?php

use App\Modules\Admin\Http\Controllers\TenantController;
use App\Support\Routing\PortalRoutes;
use Illuminate\Support\Facades\Route;

PortalRoutes::admin(function (): void {
    Route::get('/tenants', [TenantController::class, 'index'])
        ->name('tenants.index');
});

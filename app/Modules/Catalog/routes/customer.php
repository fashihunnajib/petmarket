
<?php

use App\Modules\Catalog\Http\Controllers\PublicCatalogController;
use App\Support\Routing\PortalRoutes;
use Illuminate\Support\Facades\Route;

PortalRoutes::customer(function (): void {
    Route::get('/', [PublicCatalogController::class, 'index'])
        ->name('home');
});

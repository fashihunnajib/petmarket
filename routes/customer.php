
<?php

use App\Modules\Catalog\Http\Controllers\PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCatalogController::class, 'index'])
    ->name('home');

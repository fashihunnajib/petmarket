
<?php

use App\Modules\Catalog\Http\Controllers\PublicCatalogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
|
| Route katalog publik didaftarkan melalui file ini.
|
*/

Route::get('/', [PublicCatalogController::class, 'index'])
    ->name('home');

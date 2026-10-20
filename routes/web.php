<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Baris updateStatus dihapus dari sini karena sudah dipindah ke rute PortalRoutes::admin
});

require __DIR__.'/settings.php';

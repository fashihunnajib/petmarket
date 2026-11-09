<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        // Jika user bertipe tenant, langsung lempar ke halaman pesanan
        if (auth()->user()?->hasRole('tenant')) {
            return redirect()->route('tenant.orders.index');
        }

        return view('dashboard');
    })->name('dashboard');
});

// Baris updateStatus sudah dipindahkan ke rute PortalRoutes::admin

require __DIR__.'/settings.php';

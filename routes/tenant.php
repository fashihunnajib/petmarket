<?php

use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\LocationController;
use App\Http\Controllers\Tenant\ProductController;
use Illuminate\Support\Facades\Route;

/**
 * Konteks OPERATOR TENANT (internal). Prefix: tenant/{tenant:slug}, name: tenant.*
 */
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- MODUL PRODUK TENANT ---
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// WARN: /products/create WAJIB berada DI ATAS /products/{product}
Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');

Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])->name('products.update');
Route::patch('/products/{product}/toggle', [ProductController::class, 'toggleStatus'])->name('products.toggle');
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

// --- MODUL PENARIKAN DANA TENANT ---
Route::get('/withdrawals', function () {
    return back()->with('success', 'Fitur Penarikan Dana sedang disiapkan.');
})->name('withdrawals.index');

// --- MODUL LOKASI & RADIUS ---
Route::get('/location', [LocationController::class, 'index'])->name('location.index');
Route::put('/location', [LocationController::class, 'update'])->name('location.update');

<?php

use Illuminate\Support\Facades\Route;

/**
 * Konteks PELANGGAN (publik, anonim). Prefix: kantin/{store}, name: customer.*
 * Katalog & pemesanan diisi Modul 7–9; model binding store pada Modul 4.
 */
Route::get('/', function (string $store) {
    return view('customer.home', ['store' => $store]);
})->name('home');

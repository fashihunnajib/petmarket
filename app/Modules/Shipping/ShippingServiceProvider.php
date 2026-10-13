<?php

namespace App\Modules\Shipping;

use App\Modules\ModuleServiceProvider;

/**
 * Modul: Shipping (alias `shipping`).
 * Tanggung jawab: Kitchen Display System realtime, state machine order, notifikasi (Modul 12).
 *
 * Titik perakitan modul: binding container di register(); route (routes/*.php), view
 * (`shipping::`) dan komponen Livewire (`<livewire:shipping::...>`) dimuat oleh
 * ModuleServiceProvider::boot(). Batas antarmodul ditegakkan lewat kontrak & event,
 * bukan akses langsung tabel/controller modul lain.
 */
final class ShippingServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // Binding kontrak -> implementasi ditambahkan saat modul diimplementasikan.
    }

    protected function moduleAlias(): string
    {
        return 'shipping';
    }
}

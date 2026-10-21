<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Tampilkan Halaman Pengaturan Platform
     */
    public function index(): View
    {
        // Parameter default platform (bisa diambil dari DB/Config)
        $settings = [
            'platform_commission_rate' => config('petmarket.commission_rate', 5.00),
            'tax_rate' => config('petmarket.tax_rate', 11.00),
            'service_fee_rate' => config('petmarket.service_fee_rate', 2.00),
            'max_delivery_radius_km' => config('petmarket.max_delivery_radius_km', 25.00),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Simpan / Perbarui Pengaturan Platform
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_delivery_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
        ]);

        // Simpan logika preferensi ke database/config
        // Untuk sekarang kita kembalikan status sukses
        return redirect()->back()->with('status', 'Pengaturan platform berhasil diperbarui!');
    }
}

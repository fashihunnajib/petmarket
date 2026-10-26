<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): View
    {
        $defaultSettings = [
            'platform_commission_rate' => 5.00,
            'tax_rate' => 11.00,
            'service_fee_rate' => 1.00,
            'max_delivery_radius_km' => 10.0,
        ];

        $dbSettings = Setting::pluck('value', 'key')->toArray();
        $settings = array_merge($defaultSettings, $dbSettings);

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_delivery_radius_km' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()
            ->back()
            ->with('status', 'Pengaturan platform berhasil diperbarui.');
    }
}

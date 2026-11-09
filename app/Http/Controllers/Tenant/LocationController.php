<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('tenant.location.index', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'max_delivery_radius_km' => 'required|numeric|min:1|max:100',
        ]);

        DB::table('tenants')->where('id', $tenant->id)->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'max_delivery_radius_km' => $validated['max_delivery_radius_km'],
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Radius pengiriman berhasil diperbarui!');
    }
}
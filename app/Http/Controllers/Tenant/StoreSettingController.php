<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $tenant->loadCount(['products', 'orders']);

        $commissionRate = null;
        if (Schema::hasTable('commission_schemes')) {
            $rate = DB::table('commission_schemes')
                ->where('tenant_id', $tenant->id)
                ->where(function ($query): void {
                    $query->whereNull('valid_to')
                        ->orWhere('valid_to', '>=', now());
                })
                ->orderByDesc('valid_from')
                ->value('commission_rate');

            if (is_numeric($rate)) {
                $commissionRate = (float) $rate;
            }
        }

        return view('tenant.settings.index', compact('tenant', 'commissionRate'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $rawSlug = is_string($request->input('slug')) ? Str::slug($request->input('slug')) : '';
        $request->merge(['slug' => $rawSlug]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenants', 'slug')->ignore($tenant->id),
            ],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'slug.required' => 'Slug URL toko wajib diisi.',
            'slug.unique' => 'Slug URL toko sudah digunakan oleh toko lain.',
        ]);

        $name = is_string($validated['name']) ? trim($validated['name']) : $tenant->name;
        $slug = is_string($validated['slug']) && $validated['slug'] !== '' ? $validated['slug'] : $tenant->slug;

        $tenant->update([
            'name' => $name,
            'slug' => $slug,
        ]);

        return redirect()
            ->route('tenant.settings.index', ['tenant' => $tenant->slug])
            ->with('success', 'Profil toko berhasil diperbarui.');
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        if (in_array($tenant->status, ['suspended', 'pending'], true)) {
            return redirect()
                ->back()
                ->with('error', 'Status toko sedang dikunci oleh Admin platform dan tidak dapat diubah mandiri.');
        }

        $nextStatus = $tenant->status === 'active' ? 'inactive' : 'active';

        $tenant->update([
            'status' => $nextStatus,
        ]);

        $message = $nextStatus === 'active'
            ? 'Toko berhasil dibuka dan siap menerima pesanan.'
            : 'Toko ditutup sementara.';

        return redirect()
            ->back()
            ->with('success', $message);
    }
}

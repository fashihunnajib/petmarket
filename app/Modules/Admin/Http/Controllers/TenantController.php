<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Menampilkan daftar tenant beserta fitur pencarian dan kalkulasi ringkasan.
     */
    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));

        // Query statistik ringkasan
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $pendingTenants = Tenant::where('status', 'pending')->count();

        // Query pencarian data tenant dengan eager loading relasi users
        $tenants = Tenant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tenants.index', compact(
            'tenants',
            'search',
            'totalTenants',
            'activeTenants',
            'pendingTenants'
        ));
    }

    /**
     * Menampilkan form tambah tenant baru.
     */
    public function create(): View
    {
        return view('admin.tenants.create');
    }

    /**
     * Menyimpan data tenant baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:tenants,code'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'latitude' => ['nullable', 'string', 'max:50'],
            'longitude' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
        ]);

        Tenant::create($validated);

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', 'Tenant baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail informasi dan statistik satu tenant.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->loadCount(['products', 'orders'])
            ->load(['balance']);

        return view('admin.tenants.show', compact('tenant'));
    }

    /**
     * Menampilkan form edit tenant.
     */
    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    /**
     * Memperbarui data tenant yang sudah ada.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('tenants', 'code')->ignore($tenant->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'latitude' => ['nullable', 'string', 'max:50'],
            'longitude' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', "Data tenant {$tenant->name} berhasil diperbarui.");
    }

    /**
     * Mengubah status tenant secara instan lewat sakelar ON/OFF di tabel.
     */
    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
        ]);

        $tenant->update(['status' => $validated['status']]);

        $statusLabel = $validated['status'] === 'active' ? 'diaktifkan (ON)' : 'dinonaktifkan (OFF)';

        return redirect()
            ->back()
            ->with('status', "Tenant {$tenant->name} berhasil {$statusLabel}.");
    }

    /**
     * Menghapus data tenant dari sistem.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenantName = $tenant->name;
        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', "Tenant {$tenantName} berhasil dihapus.");
    }
}

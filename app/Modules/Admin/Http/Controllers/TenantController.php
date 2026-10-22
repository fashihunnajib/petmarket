<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $tenants = Tenant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
            'totalTenants' => Tenant::count(),
            'activeTenants' => Tenant::where('status', 'active')->count(),
            'pendingTenants' => Tenant::where('status', 'pending')->count(),
        ]);
    }

    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:active,pending,suspended,inactive'],
        ]);

        $tenant->status = $request->status;
        $tenant->save();

        return redirect()->back()->with('status', 'Status tenant berhasil diperbarui!');
    }

    // 1. Menampilkan Form Tambah Tenant
    public function create(): View
    {
        return view('admin.tenants.create');
    }

    // 2. Menyimpan Tenant Baru ke Database
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:tenants,code'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'status' => ['required', 'in:active,pending,suspended'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        Tenant::create($validated);

        return redirect()->route('admin.tenants.index')
            ->with('status', 'Tenant baru berhasil ditambahkan!');
    }

    // 3. Menghapus Tenant
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()->back()
            ->with('status', 'Tenant '.$tenant->name.' berhasil dihapus!');
    }

    // 4. Menampilkan Halaman Detail Tenant
    public function show(Tenant $tenant): View
    {
        $tenant->loadCount(['products', 'orders'])
            ->load('balance');

        return view('admin.tenants.show', [
            'tenant' => $tenant,
        ]);
    }

    // 5. Menampilkan Form Edit Tenant
    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', [
            'tenant' => $tenant,
        ]);
    }

    // 6. Memperbarui Data Tenant
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:tenants,code,'.$tenant->id],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug,'.$tenant->id],
            'status' => ['required', 'in:active,pending,suspended'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $tenant->update($validated);

        return redirect()->route('admin.tenants.index')
            ->with('status', 'Data tenant '.$tenant->name.' berhasil diperbarui!');
    }
}

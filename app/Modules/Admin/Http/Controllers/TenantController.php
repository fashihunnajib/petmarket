<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Menampilkan daftar tenant beserta fitur pencarian dan kalkulasi ringkasan.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        // Query statistik ringkasan
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $pendingTenants = Tenant::where('status', 'pending')->count();

        // Query pencarian data tenant
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
     * Menampilkan form tambah tenant baru beserta daftar user terdaftar.
     */
    public function create(): View
    {
        $users = DB::table('users')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.tenants.create', compact('users'));
    }

    /**
     * Menyimpan data tenant baru beserta akun login pemilik toko ke database.
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

            // Validasi Akun Pemilik Toko
            'owner_mode' => ['required', Rule::in(['new', 'existing'])],
            'owner_name' => ['required_if:owner_mode,new', 'nullable', 'string', 'max:255'],
            'owner_email' => ['required_if:owner_mode,new', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required_if:owner_mode,new', 'nullable', 'string', 'min:6'],
            'user_id' => ['required_if:owner_mode,existing', 'nullable', 'exists:users,id'],
        ]);

        $ownerEmailInfo = '';

        DB::transaction(function () use ($validated, &$ownerEmailInfo): void {
            // 1. Buat data toko (Tenant)
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'slug' => $validated['slug'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'max_delivery_radius_km' => 10,
                'status' => $validated['status'],
            ]);

            // 2. Buat saldo awal Rp 0 jika tabel tenant_balances tersedia
            if (Schema::hasTable('tenant_balances')) {
                $existsBalance = DB::table('tenant_balances')
                    ->where('tenant_id', $tenant->id)
                    ->exists();

                if (! $existsBalance) {
                    DB::table('tenant_balances')->insert([
                        'tenant_id' => $tenant->id,
                        'available_amount' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 3. Buat akun baru atau ambil user terdaftar
            if ($validated['owner_mode'] === 'new') {
                $ownerName = is_string($validated['owner_name'] ?? null) && $validated['owner_name'] !== ''
                    ? $validated['owner_name']
                    : 'Owner '.(string) $validated['name'];
                $ownerEmail = is_string($validated['owner_email'] ?? null) ? $validated['owner_email'] : '';
                $ownerPassword = is_string($validated['owner_password'] ?? null) ? $validated['owner_password'] : 'password';

                $userData = [
                    'name' => $ownerName,
                    'email' => $ownerEmail,
                    'password' => Hash::make($ownerPassword),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('users', 'role')) {
                    $userData['role'] = 'tenant';
                }

                $userId = DB::table('users')->insertGetId($userData);
                $ownerEmailInfo = $ownerEmail;
            } else {
                $userId = is_numeric($validated['user_id'] ?? null) ? (int) $validated['user_id'] : 0;
                $existingEmail = DB::table('users')->where('id', $userId)->value('email');
                $ownerEmailInfo = is_string($existingEmail) ? $existingEmail : '';
            }

            // 4. Hubungkan User ke Tenant pada tabel user_tenant_roles
            if ($userId > 0) {
                $this->assignTenantOwner((int) $tenant->id, $userId);
            }
        });

        $message = $ownerEmailInfo !== ''
            ? "Tenant baru berhasil ditambahkan. Akun login pemilik ({$ownerEmailInfo}) siap digunakan."
            : 'Tenant baru berhasil ditambahkan.';

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', $message);
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
        $users = DB::table('users')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $currentOwner = DB::table('user_tenant_roles')
            ->join('users', 'users.id', '=', 'user_tenant_roles.user_id')
            ->where('user_tenant_roles.tenant_id', $tenant->id)
            ->select('users.id', 'users.name', 'users.email')
            ->first();

        return view('admin.tenants.edit', compact('tenant', 'users', 'currentOwner'));
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

            // Opsional pengelolaan pemilik toko saat edit
            'owner_mode' => ['nullable', Rule::in(['keep', 'new', 'existing'])],
            'owner_name' => ['required_if:owner_mode,new', 'nullable', 'string', 'max:255'],
            'owner_email' => ['required_if:owner_mode,new', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required_if:owner_mode,new', 'nullable', 'string', 'min:6'],
            'user_id' => ['required_if:owner_mode,existing', 'nullable', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($validated, $tenant): void {
            $tenant->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'slug' => $validated['slug'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => $validated['status'],
            ]);

            $ownerMode = is_string($validated['owner_mode'] ?? null) ? $validated['owner_mode'] : 'keep';

            if ($ownerMode === 'new') {
                $ownerName = is_string($validated['owner_name'] ?? null) && $validated['owner_name'] !== ''
                    ? $validated['owner_name']
                    : 'Owner '.(string) $validated['name'];
                $ownerEmail = is_string($validated['owner_email'] ?? null) ? $validated['owner_email'] : '';
                $ownerPassword = is_string($validated['owner_password'] ?? null) ? $validated['owner_password'] : 'password';

                $userData = [
                    'name' => $ownerName,
                    'email' => $ownerEmail,
                    'password' => Hash::make($ownerPassword),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('users', 'role')) {
                    $userData['role'] = 'tenant';
                }

                $newUserId = DB::table('users')->insertGetId($userData);
                DB::table('user_tenant_roles')->where('tenant_id', $tenant->id)->delete();
                $this->assignTenantOwner((int) $tenant->id, $newUserId);
            } elseif ($ownerMode === 'existing') {
                $existingUserId = is_numeric($validated['user_id'] ?? null) ? (int) $validated['user_id'] : 0;
                if ($existingUserId > 0) {
                    DB::table('user_tenant_roles')->where('tenant_id', $tenant->id)->delete();
                    $this->assignTenantOwner((int) $tenant->id, $existingUserId);
                }
            }
        });

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
     * Menghapus data tenant dari sistem (membebaskan code & slug agar bisa dipakai ulang).
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenantName = $tenant->name;

        $tenant->update([
            'code' => $tenant->code . '_del_' . $tenant->id,
            'slug' => $tenant->slug . '-del-' . $tenant->id,
        ]);

        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', "Tenant {$tenantName} berhasil dihapus.");
    }

    /**
     * Menghubungkan user ke tenant sebagai pemilik di tabel user_tenant_roles.
     */
    private function assignTenantOwner(int $tenantId, int $userId): void
    {
        if (Schema::hasColumn('users', 'role')) {
            $currentRole = DB::table('users')->where('id', $userId)->value('role');
            if ($currentRole !== 'admin') {
                DB::table('users')->where('id', $userId)->update([
                    'role' => 'tenant',
                    'updated_at' => now(),
                ]);
            }
        }

        $pivotData = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ];

        if (Schema::hasColumn('user_tenant_roles', 'role')) {
            $pivotData['role'] = 'owner';
        } elseif (Schema::hasColumn('user_tenant_roles', 'role_id')) {
            $roleId = Schema::hasTable('roles')
                ? (DB::table('roles')->where('name', 'owner')->value('id') ?? DB::table('roles')->value('id'))
                : null;
            $pivotData['role_id'] = is_numeric($roleId) ? (int) $roleId : 1;
        }

        if (Schema::hasColumn('user_tenant_roles', 'created_at')) {
            $pivotData['created_at'] = now();
        }
        if (Schema::hasColumn('user_tenant_roles', 'updated_at')) {
            $pivotData['updated_at'] = now();
        }

        DB::table('user_tenant_roles')->updateOrInsert(
            ['tenant_id' => $tenantId, 'user_id' => $userId],
            $pivotData
        );
    }
}

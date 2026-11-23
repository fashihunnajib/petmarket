<?php

namespace App\Http\Controllers;

use App\Mail\TenantRegisteredMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $existingTenant = DB::table('user_tenant_roles')
            ->join('tenants', 'tenants.id', '=', 'user_tenant_roles.tenant_id')
            ->where('user_tenant_roles.user_id', $user->id)
            ->whereNull('tenants.deleted_at')
            ->select('tenants.*')
            ->first();

        return view('tenant.register', compact('user', 'existingTenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $alreadyHasTenant = DB::table('user_tenant_roles')
            ->join('tenants', 'tenants.id', '=', 'user_tenant_roles.tenant_id')
            ->where('user_tenant_roles.user_id', $user->id)
            ->whereNull('tenants.deleted_at')
            ->exists();

        if ($alreadyHasTenant) {
            return redirect()
                ->route('buka-toko')
                ->with('error', 'Akun Anda sudah terdaftar sebagai pemilik toko.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'max_delivery_radius_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'slug.unique' => 'URL toko tersebut sudah dipakai, silakan gunakan nama lain.',
            'slug.alpha_dash' => 'URL toko hanya boleh berisi huruf, angka, dan tanda strip (-).',
        ]);

        $tenantName = trim((string) $validated['name']);
        $baseSlug = is_string($validated['slug'] ?? null) && $validated['slug'] !== ''
            ? Str::slug($validated['slug'])
            : Str::slug($tenantName);

        if ($baseSlug === '') {
            $baseSlug = 'toko-'.strtolower(Str::random(6));
        }

        $slug = $baseSlug;
        $counter = 1;
        while (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        do {
            $code = 'SHOP-'.strtoupper(Str::random(6));
        } while (Tenant::withTrashed()->where('code', $code)->exists());

        /** @var Tenant $tenant */
        $tenant = DB::transaction(function () use ($user, $tenantName, $slug, $code, $validated): Tenant {
            $tenant = Tenant::create([
                'name' => $tenantName,
                'code' => $code,
                'slug' => $slug,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'max_delivery_radius_km' => $validated['max_delivery_radius_km'] ?? 10,
                'status' => 'pending',
            ]);

            if (Schema::hasTable('tenant_balances')) {
                DB::table('tenant_balances')->insert([
                    'tenant_id' => $tenant->id,
                    'available_amount' => 0,
                    'held_amount' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasColumn('users', 'role') && $user->role !== 'admin') {
                DB::table('users')->where('id', $user->id)->update([
                    'role' => 'tenant',
                    'updated_at' => now(),
                ]);
            }

            DB::table('user_tenant_roles')->insert([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $tenant;
        });

        try {
            Mail::to($user->email)->send(new TenantRegisteredMail($tenant, $user));
            $mailStatus = "Notifikasi konfirmasi telah dikirim ke email {$user->email}.";
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email pendaftaran tenant: '.$e->getMessage());
            $mailStatus = 'Pendaftaran berhasil, namun email notifikasi gagal dikirim (periksa konfigurasi MAIL di .env).';
        }

        return redirect()
            ->route('buka-toko')
            ->with('success', "Toko {$tenant->name} berhasil didaftarkan dan menunggu verifikasi Admin. {$mailStatus}");
    }
}

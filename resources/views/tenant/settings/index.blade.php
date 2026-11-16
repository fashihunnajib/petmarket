<x-layouts.tenant :tenant="$tenant" title="Profil & Pengaturan Toko">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
        $isActive = $tenant->status === 'active';
        $isLocked = in_array($tenant->status, ['suspended', 'pending'], true);
        $statusLabel = match ($tenant->status) {
            'active' => 'Toko Buka (Aktif)',
            'inactive' => 'Tutup Sementara',
            'pending' => 'Menunggu Verifikasi Admin',
            'suspended' => 'Dinonaktifkan Admin',
            default => ucfirst((string) $tenant->status),
        };
    @endphp

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Profil & Pengaturan Toko</h2>
        <p class="text-xs text-slate-500 mt-1">Kelola identitas toko, alamat tautan toko, dan status operasional harian Anda.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-medium rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri (2 Kolom): Identitas Toko -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-5 h-fit">
            <div>
                <h3 class="text-base font-bold text-slate-900">Identitas Toko</h3>
                <p class="text-xs text-slate-400 mt-0.5">Informasi ini ditampilkan kepada pelanggan pada katalog dan halaman checkout.</p>
            </div>

            <form method="POST" action="{{ route('tenant.settings.update', ['tenant' => $tenantSlug]) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Toko</label>
                    <input type="text"
                           value="{{ $tenant->code }}"
                           disabled
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-50 text-slate-500 border border-slate-200 rounded-xl cursor-not-allowed">
                    <span class="text-[11px] text-slate-400 mt-1 block">Kode unik toko ditetapkan oleh sistem dan tidak dapat diubah.</span>
                </div>

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1">Nama Toko *</label>
                    <input id="name"
                           type="text"
                           name="name"
                           value="{{ old('name', $tenant->name) }}"
                           required
                           maxlength="120"
                           class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @error('name')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="block text-xs font-bold text-slate-700 mb-1">Slug URL Toko *</label>
                    <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500">
                        <span class="px-3 py-2.5 bg-slate-50 text-[11px] text-slate-400 border-r border-slate-200 select-none">
                            /tenant/
                        </span>
                        <input id="slug"
                               type="text"
                               name="slug"
                               value="{{ old('slug', $tenant->slug) }}"
                               required
                               maxlength="100"
                               class="w-full px-3 py-2.5 text-xs border-0 focus:ring-0">
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Gunakan huruf kecil, angka, dan tanda hubung (-). Contoh: <code>petonar-shop</code>.</span>
                    @error('slug')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pt-2 flex items-center justify-between gap-4 border-t border-slate-100">
                    <a href="{{ route('tenant.location.index', ['tenant' => $tenantSlug]) }}"
                       class="text-xs font-semibold text-emerald-600 hover:underline">
                        Atur Lokasi & Radius Pengiriman →
                    </a>

                    <button type="submit"
                            class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan (1 Kolom): Status Operasional & Ringkasan Tarif -->
        <div class="space-y-6">
            <!-- Status Operasional -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Status Operasional</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Kontrol ketersediaan toko menerima pesanan baru.</p>
                </div>

                <div class="p-3.5 rounded-xl border {{ $isActive ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50 border-slate-200' }} flex items-center justify-between gap-3">
                    <div>
                        <span class="text-[11px] text-slate-500 block">Status Saat Ini</span>
                        <span class="text-xs font-bold {{ $isActive ? 'text-emerald-700' : 'text-slate-700' }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <span class="w-3 h-3 rounded-full {{ $isActive ? 'bg-emerald-500' : ($isLocked ? 'bg-red-500' : 'bg-slate-400') }}"></span>
                </div>

                @if($isLocked)
                    <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3">
                        Status toko sedang dikunci oleh Admin platform. Hubungi administrator jika memerlukan perubahan status.
                    </p>
                @else
                    <form method="POST" action="{{ route('tenant.settings.toggle-status', ['tenant' => $tenantSlug]) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold transition-all {{ $isActive ? 'border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm' }}">
                            {{ $isActive ? 'Tutup Toko Sementara' : 'Buka Toko Sekarang' }}
                        </button>
                    </form>
                @endif
            </div>

            <!-- Ringkasan Tarif Platform -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-3">
                <h3 class="text-base font-bold text-slate-900">Ketentuan & Tarif Toko</h3>

                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Radius Pengiriman</span>
                        <span class="font-semibold text-slate-800">{{ number_format((float) $tenant->max_delivery_radius_km, 1, ',', '.') }} km</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Pajak (PPN)</span>
                        <span class="font-semibold text-slate-800">{{ number_format((float) $tenant->tax_rate * 100, 1, ',', '.') }}%</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Biaya Layanan</span>
                        <span class="font-semibold text-slate-800">{{ number_format((float) $tenant->service_fee_rate * 100, 1, ',', '.') }}%</span>
                    </div>
                    @if($commissionRate !== null)
                        <div class="py-2.5 flex justify-between">
                            <span class="text-slate-500">Komisi Platform</span>
                            <span class="font-semibold text-slate-800">{{ number_format($commissionRate * 100, 1, ',', '.') }}%</span>
                        </div>
                    @endif
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Total Produk / Pesanan</span>
                        <span class="font-semibold text-slate-800">{{ $tenant->products_count }} produk • {{ $tenant->orders_count }} pesanan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
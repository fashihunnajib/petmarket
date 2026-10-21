<x-layouts.admin title="Detail Tenant - {{ $tenant->name }}">
    <div class="space-y-6">
        <!-- Header & Navigasi -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ $tenant->name }}
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Kode Tenant: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $tenant->code }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                    Edit Tenant
                </a>
                <a href="{{ route('admin.tenants.index') }}" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                    Kembali
                </a>
            </div>
        </div>

        @php
            $statusBadgeClass = match ($tenant->status) {
                'active' => 'text-emerald-600',
                'pending' => 'text-amber-600',
                'suspended' => 'text-red-600',
                default => 'text-zinc-600',
            };
        @endphp

        <!-- Kartu Statistik Real-time -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status Toko</p>
                <p class="mt-2 text-2xl font-bold uppercase tracking-wide {{ $statusBadgeClass }}">
                    {{ $tenant->status }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Produk</p>
                <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format($tenant->products_count ?? 0) }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Pesanan</p>
                <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format($tenant->orders_count ?? 0) }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo Ready (Pencairan)</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    Rp {{ number_format($tenant->balance?->available_amount ?? 0, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <!-- Informasi Detail & Peta Lokasi -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Informasi Profil -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="border-b border-zinc-200 pb-3 text-base font-bold text-zinc-900 dark:border-zinc-800 dark:text-white">
                    Informasi Toko
                </h3>
                
                <div class="mt-4 space-y-4">
                    <div>
                        <span class="block text-xs font-medium text-zinc-400">Nama Slug URL</span>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">/{{ $tenant->slug }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-zinc-400">Koordinat Latitude</span>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $tenant->latitude ?? 'Belum diatur' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-zinc-400">Koordinat Longitude</span>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $tenant->longitude ?? 'Belum diatur' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-zinc-400">Terdaftar Sejak</span>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $tenant->created_at ? $tenant->created_at->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Integrasi Peta Google Maps (Embed) -->
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="border-b border-zinc-200 pb-3 text-base font-bold text-zinc-900 dark:border-zinc-800 dark:text-white">
                    Lokasi Peta
                </h3>
                
                <div class="mt-4 min-h-[250px] w-full flex-1 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                    @if($tenant->latitude && $tenant->longitude)
                        <iframe 
                            width="100%" 
                            height="100%" 
                            style="border:0; min-height: 250px;" 
                            loading="lazy" 
                            allowfullscreen 
                            src="https://maps.google.com/maps?q={{ $tenant->latitude }},{{ $tenant->longitude }}&hl=id&z=15&output=embed">
                        </iframe>
                    @else
                        <div class="flex h-full min-h-[250px] items-center justify-center text-xs text-zinc-400">
                            Koordinat latitude/longitude belum tersedia.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buka Toko - {{ config('app.name', 'PetMarket') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900">Buka Toko di PetMarket</h1>
                <p class="text-xs text-slate-500 mt-1">Daftarkan pet shop Anda dan mulai berjualan.</p>
            </div>
            <a href="{{ url('/dashboard') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                &larr; Kembali
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if($existingTenant)
            <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900 text-sm">{{ $existingTenant->name }}</span>
                    <span class="px-2.5 py-1 rounded-full font-semibold {{ $existingTenant->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $existingTenant->status === 'active' ? 'Aktif' : 'Menunggu Verifikasi' }}
                    </span>
                </div>
                <p class="text-slate-500">Kode Toko: <strong class="text-slate-700">{{ $existingTenant->code }}</strong></p>
                <p class="text-slate-500">Email Pemilik: <strong class="text-slate-700">{{ $user->email }}</strong></p>

                @if($existingTenant->status === 'active')
                    <div class="pt-2">
                        <a href="{{ route('tenant.dashboard', ['tenant' => $existingTenant->slug]) }}"
                           class="inline-block px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold">
                            Masuk ke Panel Toko
                        </a>
                    </div>
                @endif
            </div>
        @else
            <form method="POST" action="{{ route('buka-toko.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Penerima Notifikasi</label>
                    <input type="text" value="{{ $user->email }}" disabled
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-100 border border-slate-200 rounded-xl text-slate-500">
                </div>

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1">Nama Toko *</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                           placeholder="Contoh: Banyuwangi Pet Care"
                           class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @error('name')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="block text-xs font-bold text-slate-700 mb-1">Slug / URL Toko (Opsional)</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug') }}"
                           placeholder="Otomatis dari nama toko jika dikosongkan"
                           class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @error('slug')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="latitude" class="block text-xs font-bold text-slate-700 mb-1">Latitude (Opsional)</label>
                        <input id="latitude" type="text" name="latitude" value="{{ old('latitude', '-8.2192') }}"
                               class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                    </div>
                    <div>
                        <label for="longitude" class="block text-xs font-bold text-slate-700 mb-1">Longitude (Opsional)</label>
                        <input id="longitude" type="text" name="longitude" value="{{ old('longitude', '114.3691') }}"
                               class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                    </div>
                </div>

                <div>
                    <label for="max_delivery_radius_km" class="block text-xs font-bold text-slate-700 mb-1">Radius Pengiriman Maksimal (km)</label>
                    <input id="max_delivery_radius_km" type="number" name="max_delivery_radius_km" value="{{ old('max_delivery_radius_km', 15) }}" min="1" max="100"
                           class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-2.5 px-5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs shadow-md shadow-emerald-500/20 transition">
                        Daftarkan Toko & Kirim Notifikasi Email
                    </button>
                </div>
            </form>
        @endif
    </div>
</body>
</html>
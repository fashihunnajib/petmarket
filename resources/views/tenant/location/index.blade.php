<x-layouts.tenant :tenant="$tenant" title="Lokasi & Radius">
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Lokasi & Radius Layanan</h2>
        <p class="text-xs text-slate-500 mt-1">Atur titik lokasi toko dan jangkauan radius pengiriman produk Anda.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm max-w-2xl">
        <form method="POST" action="{{ route('tenant.location.update', ['tenant' => $tenant->slug ?? $tenant->id]) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Radius Layanan Maksimal (KM) *</label>
                <input type="number" step="0.1" name="max_delivery_radius_km" 
                       value="{{ old('max_delivery_radius_km', $tenant->max_delivery_radius_km ?? 15) }}" 
                       class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <span class="text-[10px] text-slate-400 mt-1 block">Pelanggan di luar radius ini tidak dapat memilih opsi pengiriman instan/lokal.</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude</label>
                    <input type="text" name="latitude" value="{{ old('latitude', $tenant->latitude ?? '-6.9175') }}" 
                           class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude</label>
                    <input type="text" name="longitude" value="{{ old('longitude', $tenant->longitude ?? '107.6191') }}" 
                           class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-layouts.tenant>
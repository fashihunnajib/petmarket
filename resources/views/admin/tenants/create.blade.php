<x-layouts.admin title="Tambah Tenant Baru">
    <div class="space-y-6 max-w-3xl">
        <!-- Header Judul dengan Garis Hijau -->
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">Tambah Tenant Baru</h2>
            <div class="h-[2px] w-full bg-emerald-400"></div>
        </div>

        <!-- Action / Subheader Bar -->
        <div class="flex items-center justify-between rounded-sm border border-emerald-400 bg-white p-4">
            <p class="text-sm font-bold text-gray-900">
                Daftarkan toko/tenant baru ke sistem PetMarket.
            </p>
            <a href="{{ route('admin.tenants.index') }}" class="rounded-sm border border-gray-300 bg-white px-4 py-1.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                Kembali
            </a>
        </div>

        <!-- Box Utama Form -->
        <div class="rounded-sm border border-emerald-400 bg-white p-6">
            <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-1">Nama Tenant / Toko</label>
                    <input type="text" id="tenant-name" name="name" value="{{ old('name') }}" required 
                           class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none" 
                           placeholder="Contoh: Petshop Sejahtera">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-1">Kode Tenant</label>
                        <input type="text" name="code" value="{{ old('code') }}" required 
                               class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none" 
                               placeholder="PST01">
                        @error('code')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-1">Slug URL</label>
                        <input type="text" id="tenant-slug" name="slug" value="{{ old('slug') }}" required 
                               class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none" 
                               placeholder="petshop-sejahtera">
                        @error('slug')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

<!-- CDN CSS Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="space-y-4">
    <label class="block text-sm font-bold text-gray-900">Pilih Lokasi Toko di Peta</label>
    
    <!-- Wadah Peta -->
    <div id="map" class="h-64 w-full rounded-sm border border-emerald-400"></div>

    <!-- Preview Posisi Koordinat -->
    <div class="rounded-sm border border-emerald-300 bg-emerald-50 p-2 text-xs font-medium text-emerald-900">
        📍 Posisi Koordinat Terpilih: <span id="coordinate-preview" class="font-bold">-8.2192, 114.3692</span>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-900 mb-1">Latitude</label>
            <input type="text" id="latitude" name="latitude" value="{{ old('latitude', $tenant->latitude ?? '-8.2192') }}" 
                   class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none"
                   placeholder="Contoh: -8.2192">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-900 mb-1">Longitude</label>
            <input type="text" id="longitude" name="longitude" value="{{ old('longitude', $tenant->longitude ?? '114.3692') }}" 
                   class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none"
                   placeholder="Contoh: 114.3692">
        </div>
    </div>
</div>

            <!-- CDN JS Leaflet & Script Sinkronisasi 2 Arah -->
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const latInput = document.getElementById('latitude');
                    const lngInput = document.getElementById('longitude');
                    const previewText = document.getElementById('coordinate-preview');

                    // Ambil nilai awal
                    let defaultLat = parseFloat(latInput.value) || -8.2192;
                    let defaultLng = parseFloat(lngInput.value) || 114.3692;

                    // Render peta
                    const map = L.map('map').setView([defaultLat, defaultLng], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap'
                    }).addTo(map);

                    // Marker / Pin yang bisa digeser
                    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

                    // Fungsi memperbarui tampilan angka dan teks preview
                    function updateUI(lat, lng, moveMap = false) {
                        const formattedLat = parseFloat(lat).toFixed(6);
                        const formattedLng = parseFloat(lng).toFixed(6);

                        latInput.value = formattedLat;
                        lngInput.value = formattedLng;
                        previewText.textContent = `${formattedLat}, ${formattedLng}`;

                        if (moveMap) {
                            marker.setLatLng([lat, lng]);
                            map.panTo([lat, lng]);
                        }
                    }

                    // 1. CARA PERTAMA: Geser pin atau klik peta
                    marker.on('dragend', function() {
                        const pos = marker.getLatLng();
                        updateUI(pos.lat, pos.lng);
                    });

                    map.on('click', function(e) {
                        updateUI(e.latlng.lat, e.latlng.lng, true);
                    });

                    // 2. CARA KEDUA: Ketik manual angka di kolom input
                    function handleManualInput() {
                        const latVal = parseFloat(latInput.value);
                        const lngVal = parseFloat(lngInput.value);

                        if (!isNaN(latVal) && !isNaN(lngVal)) {
                            updateUI(latVal, lngVal, true);
                        }
                    }

                    latInput.addEventListener('change', handleManualInput);
                    lngInput.addEventListener('change', handleManualInput);

                    // Inisialisasi awal
                    updateUI(defaultLat, defaultLng);
                });
            </script>

                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-1">Status Awal</label>
                    <select name="status" class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="active">Active (Langsung Aktif)</option>
                        <option value="pending">Pending (Menunggu Verifikasi)</option>
                        <option value="suspended">Suspended (Ditangguhkan)</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-sm border border-emerald-400 bg-emerald-100 py-2.5 text-sm font-bold text-gray-900 transition hover:bg-emerald-200">
                        Simpan Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Auto-Slug Otomatis -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('tenant-name');
            const slugInput = document.getElementById('tenant-slug');

            if (nameInput && slugInput) {
                nameInput.addEventListener('keyup', function() {
                    slugInput.value = nameInput.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w ]+/g, '')
                        .replace(/ +/g, '-');
                });
            }
        });
    </script>
</x-layouts.admin>
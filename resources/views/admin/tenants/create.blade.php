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

                <!-- Bagian Akun Login Pemilik Toko (Tenant) -->
                <div class="pt-4 border-t border-emerald-200 space-y-3">
                    <div>
                        <label class="block text-sm font-bold text-gray-900">Akun Login Pemilik Toko (Tenant)</label>
                        <p class="text-xs text-gray-500">Email & password ini dipakai pemilik toko untuk login dan langsung masuk ke dashboard tokonya.</p>
                    </div>

                    <div class="flex flex-wrap gap-5">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 cursor-pointer">
                            <input type="radio" name="owner_mode" value="new" {{ old('owner_mode', 'new') === 'new' ? 'checked' : '' }}
                                   onchange="toggleOwnerMode(this.value)" class="text-emerald-600 focus:ring-emerald-500">
                            <span>Buat Akun Baru Sekaligus</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 cursor-pointer">
                            <input type="radio" name="owner_mode" value="existing" {{ old('owner_mode') === 'existing' ? 'checked' : '' }}
                                   onchange="toggleOwnerMode(this.value)" class="text-emerald-600 focus:ring-emerald-500">
                            <span>Pilih dari User Terdaftar</span>
                        </label>
                    </div>

                    <!-- Mode 1: Buat Akun Pemilik Baru -->
                    <div id="owner-new-fields" class="grid grid-cols-1 md:grid-cols-3 gap-4 rounded-sm border border-emerald-300 bg-emerald-50/40 p-4 {{ old('owner_mode', 'new') === 'new' ? '' : 'hidden' }}">
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">Nama Pemilik *</label>
                            <input type="text" name="owner_name" value="{{ old('owner_name') }}" 
                                   class="w-full rounded-sm border border-gray-300 bg-white p-2 text-sm focus:border-emerald-500 focus:outline-none" 
                                   placeholder="Contoh: Budi Santoso">
                            @error('owner_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">Email Login *</label>
                            <input type="email" name="owner_email" value="{{ old('owner_email') }}" 
                                   class="w-full rounded-sm border border-gray-300 bg-white p-2 text-sm focus:border-emerald-500 focus:outline-none" 
                                   placeholder="toko@petmarket.test">
                            @error('owner_email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">Password Login *</label>
                            <input type="text" name="owner_password" value="{{ old('owner_password', 'password') }}" 
                                   class="w-full rounded-sm border border-gray-300 bg-white p-2 text-sm focus:border-emerald-500 focus:outline-none" 
                                   placeholder="Minimal 6 karakter">
                            @error('owner_password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Mode 2: Pilih User Terdaftar -->
                    <div id="owner-existing-fields" class="rounded-sm border border-emerald-300 bg-emerald-50/40 p-4 {{ old('owner_mode') === 'existing' ? '' : 'hidden' }}">
                        <label class="block text-xs font-bold text-gray-900 mb-1">Pilih Akun User *</label>
                        <select name="user_id" class="w-full rounded-sm border border-gray-300 bg-white p-2 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Pilih Akun User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-1">Status Awal</label>
                    <select name="status" class="w-full rounded-sm border border-gray-300 p-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active (Langsung Aktif)</option>
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending (Menunggu Verifikasi)</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended (Ditangguhkan)</option>
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

    <!-- CDN JS Leaflet & Script Sinkronisasi 2 Arah -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function toggleOwnerMode(mode) {
            document.getElementById('owner-new-fields').classList.toggle('hidden', mode !== 'new');
            document.getElementById('owner-existing-fields').classList.toggle('hidden', mode !== 'existing');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Sinkronisasi Peta & Koordinat
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const previewText = document.getElementById('coordinate-preview');

            let defaultLat = parseFloat(latInput.value) || -8.2192;
            let defaultLng = parseFloat(lngInput.value) || 114.3692;

            const map = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

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

            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                updateUI(pos.lat, pos.lng);
            });

            map.on('click', function(e) {
                updateUI(e.latlng.lat, e.latlng.lng, true);
            });

            function handleManualInput() {
                const latVal = parseFloat(latInput.value);
                const lngVal = parseFloat(lngInput.value);

                if (!isNaN(latVal) && !isNaN(lngVal)) {
                    updateUI(latVal, lngVal, true);
                }
            }

            latInput.addEventListener('change', handleManualInput);
            lngInput.addEventListener('change', handleManualInput);

            updateUI(defaultLat, defaultLng);

            // 2. Auto-Slug Otomatis dari Nama Tenant
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
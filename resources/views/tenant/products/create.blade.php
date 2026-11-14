<x-layouts.tenant :tenant="$tenant" title="Tambah Produk Baru">

    @if(request()->query('step') === 'success')
        <!-- STEP 4: SELESAI -->
        <div class="max-w-xl mx-auto py-12 text-center bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
            <div class="w-24 h-24 bg-emerald-500 rounded-full flex items-center justify-center text-white text-5xl mx-auto mb-6 shadow-lg shadow-emerald-500/30">
                ✓
            </div>
            <h2 class="text-2xl font-extrabold text-slate-800">Produk Berhasil Ditambahkan</h2>
            <p class="text-xs text-slate-500 mt-2">Katalog produk baru Anda telah aktif dan siap dipasarkan.</p>
            <div class="mt-8 flex flex-col gap-3 max-w-xs mx-auto">
                <a href="{{ route('tenant.products.create', ['tenant' => $tenant->slug ?? $tenant->id]) }}" 
                   class="w-full py-3 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition-colors">
                    + Tambah Produk Lagi
                </a>
                <a href="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id]) }}" 
                   class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all">
                    👁 Lihat Produk
                </a>
            </div>
        </div>
    @else
        <!-- Header Page -->
        <div class="mb-6">
            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Tambah Produk Baru</h2>
            <p class="text-xs text-slate-500 mt-1">Lengkapi informasi produk sesuai jenisnya</p>
        </div>

        <!-- Alert Error dari Validasi Backend -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl">
                <div class="font-bold mb-1">Gagal menyimpan produk:</div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.products.store', ['tenant' => $tenant->slug ?? $tenant->id]) }}" enctype="multipart/form-data" id="wizardForm" novalidate>
            @csrf

            <!-- STEP WIZARD PROGRESS BAR -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex items-center justify-between text-xs max-w-4xl overflow-x-auto">
                <div class="flex items-center gap-2 text-emerald-600 font-bold whitespace-nowrap" id="step1Nav">
                    <span class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs" id="badge1">1</span>
                    <span>Informasi Produk</span>
                </div>
                <div class="h-0.5 w-12 bg-slate-200" id="line1"></div>

                <div class="flex items-center gap-2 text-slate-400 font-medium whitespace-nowrap" id="step2Nav">
                    <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs" id="badge2">2</span>
                    <span>Detail Produk</span>
                </div>
                <div class="h-0.5 w-12 bg-slate-200" id="line2"></div>

                <div class="flex items-center gap-2 text-slate-400 font-medium whitespace-nowrap" id="step3Nav">
                    <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs" id="badge3">3</span>
                    <span>Lokasi & Pengiriman</span>
                </div>
                <div class="h-0.5 w-12 bg-slate-200"></div>

                <div class="flex items-center gap-2 text-slate-400 font-medium whitespace-nowrap">
                    <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs">4</span>
                    <span>Selesai</span>
                </div>
            </div>

            <!-- STEP 1: INFORMASI PRODUK -->
            <div id="step1Content" class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-5">
                    <h3 class="font-extrabold text-slate-800 text-sm">Informasi Produk</h3>

                    <!-- Toggle Jenis Produk -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Jenis Produk *</label>
                        <div class="grid grid-cols-2 gap-3 max-w-sm">
                            <button type="button" onclick="setJenisProduk('animal')" id="btnTypeAnimal" 
                                    class="py-2.5 px-4 rounded-xl text-xs font-bold border transition-all bg-emerald-500 text-white border-emerald-500 shadow-sm">
                                Hewan Hidup
                            </button>
                            <button type="button" onclick="setJenisProduk('goods')" id="btnTypeGoods" 
                                    class="py-2.5 px-4 rounded-xl text-xs font-bold border transition-all bg-white text-slate-600 border-slate-200 hover:bg-slate-50">
                                Barang
                            </button>
                        </div>
                        <input type="hidden" name="type" id="inputProductType" value="{{ old('type', 'animal') }}">
                    </div>

                    <!-- Input Nama & Kategori -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk *</label>
                            <input type="text" name="name" id="inputName" value="{{ old('name') }}" placeholder="Contoh: Kucing Persian" 
                                   oninput="updatePreview()" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <span class="text-red-500 text-[10px] hidden" id="errName">Nama produk wajib diisi</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                            <select name="category_id" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Input Harga & Stok -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Harga *</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="base_price" id="inputPrice" value="{{ old('base_price') }}" placeholder="2500000" 
                                       oninput="updatePreview()" class="w-full pl-10 pr-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>
                            <span class="text-red-500 text-[10px] hidden" id="errPrice">Harga wajib diisi</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Stok *</label>
                            <input type="number" name="stock_qty" id="inputStock" value="{{ old('stock_qty', 1) }}" placeholder="1" 
                                   oninput="updatePreview()" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <span class="text-red-500 text-[10px] hidden" id="errStock">Stok minimal 0</span>
                        </div>
                    </div>

                    <!-- Input Deskripsi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi</label>
                        <textarea name="description" rows="3" placeholder="Deskripsi produk..." 
                                  class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">{{ old('description') }}</textarea>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="button" onclick="validateAndGoStep(2)" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all">
                            Lanjutkan
                        </button>
                    </div>
                </div>

                <!-- Foto Upload Dropzone -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <label class="block text-xs font-bold text-slate-700">Foto Produk</label>
                    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-emerald-500 transition-colors cursor-pointer bg-slate-50/50" onclick="document.getElementById('filePhoto').click()">
                        <div class="text-3xl text-slate-400 mb-2">☁️</div>
                        <p class="text-xs text-slate-600 font-semibold">Klik untuk pilih foto</p>
                        <p class="text-[10px] text-slate-400 mt-1">JPG, PNG, WEBP (Max 2MB)</p>
                        <input type="file" name="photo" id="filePhoto" accept="image/*" class="hidden" onchange="previewImage(this)">
                    </div>
                    <div id="imagePreviewContainer" class="hidden">
                        <img id="imgPreview" class="w-full h-44 object-cover rounded-xl border border-slate-200">
                    </div>
                </div>
            </div>

            <!-- STEP 2: DETAIL PRODUK -->
            <div id="step2Content" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-5">
                    <h3 class="font-extrabold text-slate-800 text-sm" id="step2Title">Detail Produk (Hewan Hidup)</h3>

                    <div class="p-3.5 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-[11px] flex items-center gap-2">
                        <span>ℹ️</span> Pastikan detail spesifikasi produk sudah akurat sebelum didaftarkan.
                    </div>

                    <!-- Fields Khusus Hewan -->
                    <div id="animalFieldsSection" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Ras / Breed</label>
                                <input type="text" name="breed" id="inputBreed" value="{{ old('breed') }}" placeholder="Persian / Golden Retriever" 
                                       oninput="updatePreview()" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Usia (Bulan)</label>
                                <input type="number" name="age_months" id="inputAge" value="{{ old('age_months') }}" placeholder="8" 
                                       oninput="updatePreview()" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin</label>
                                <select name="gender" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl bg-white">
    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Betina</option>
    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Jantan</option>
</select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Vaksinasi</label>
                                <select name="vaccination_status" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl bg-white">
                                    <option value="Sudah Vaksin (Lengkap)">Sudah Vaksin (Lengkap)</option>
                                    <option value="Belum Vaksin">Belum Vaksin</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Berat Badan (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg') }}" placeholder="2" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tinggi Badan (cm)</label>
                                <input type="number" step="0.1" name="height_cm" value="{{ old('height_cm') }}" placeholder="25" class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-between">
                        <button type="button" onclick="goToStep(1)" class="px-5 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">
                            Kembali
                        </button>
                        <button type="button" onclick="goToStep(3)" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20">
                            Lanjutkan
                        </button>
                    </div>
                </div>

                <!-- Right Card Preview -->
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-700">Preview Tampilan Produk</label>
                    <div class="bg-white border border-slate-200 rounded-2xl p-3 shadow-sm">
                        <div class="w-full h-44 rounded-xl overflow-hidden bg-slate-100 mb-3 flex items-center justify-center text-slate-300 font-bold text-2xl" id="cardPreviewBox">
                            <img id="cardPreviewImg" src="" class="w-full h-full object-cover hidden">
                            <span id="cardPreviewPlaceholder">🐾</span>
                        </div>
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 font-bold text-[10px] rounded-md" id="cardPreviewBadge">Hewan Hidup</span>
                        <h4 class="font-extrabold text-slate-800 text-sm mt-2" id="cardPreviewTitle">Kucing Persian</h4>
                        <div class="font-extrabold text-slate-900 text-sm mt-1" id="cardPreviewPrice">Rp 2.500.000</div>
                        <div class="text-[10px] text-slate-400 mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                            <span>🏠 {{ $tenant->name }}</span>
                            <span id="cardPreviewStock">Stok: 1</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: LOKASI & PENGIRIMAN -->
            <div id="step3Content" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-5">
                    <h3 class="font-extrabold text-slate-800 text-sm">Lokasi & Pengiriman Toko</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Latitude</label>
                            <input type="text" value="{{ $tenant->latitude ?? '-6.9175' }}" readonly class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Longitude</label>
                            <input type="text" value="{{ $tenant->longitude ?? '107.6191' }}" readonly class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-500">
                        </div>
                    </div>

                    <div class="h-36 bg-slate-100 rounded-2xl border border-slate-200 flex items-center justify-center text-slate-400 text-xs font-medium">
                        🗺️ Max Radius Layanan Pengiriman: {{ number_format((float) ($tenant->max_delivery_radius_km ?? 0), 2) }} KM
                    </div>

                    <div class="pt-4 flex justify-between">
                        <button type="button" onclick="goToStep(2)" class="px-5 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">
                            Kembali
                        </button>
                        <button type="submit" class="px-8 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-emerald-500/30">
                            Simpan & Selesai
                        </button>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <label class="block text-xs font-bold text-slate-700">Opsi Pengiriman Didukung</label>
                    <div class="space-y-3 text-xs">
                        <label class="flex items-center gap-2.5">
                            <input type="checkbox" checked disabled class="rounded text-emerald-500">
                            <span class="font-medium text-slate-700">Instan (Kurir Lokal PetMarket)</span>
                        </label>
                        <label class="flex items-center gap-2.5">
                            <input type="checkbox" checked disabled class="rounded text-emerald-500">
                            <span class="font-medium text-slate-700">Ambil Di Tempat (Self Pick-up)</span>
                        </label>
                    </div>
                </div>
            </div>
        </form>

        <!-- SCRIPT NAVIGASI WIZARD & INTERAKSI LIVE PREVIEW -->
        <script>
            let currentStep = 1;

            function setJenisProduk(type) {
                document.getElementById('inputProductType').value = type;
                const btnAnimal = document.getElementById('btnTypeAnimal');
                const btnGoods = document.getElementById('btnTypeGoods');
                const animalFields = document.getElementById('animalFieldsSection');
                const badge = document.getElementById('cardPreviewBadge');
                const step2Title = document.getElementById('step2Title');

                if(type === 'animal') {
                    btnAnimal.className = "py-2.5 px-4 rounded-xl text-xs font-bold border transition-all bg-emerald-500 text-white border-emerald-500 shadow-sm";
                    btnGoods.className = "py-2.5 px-4 rounded-xl text-xs font-bold border transition-all bg-white text-slate-600 border-slate-200 hover:bg-slate-50";
                    animalFields.classList.remove('hidden');
                    badge.innerText = 'Hewan Hidup';
                    badge.className = 'px-2.5 py-1 bg-emerald-100 text-emerald-700 font-bold text-[10px] rounded-md';
                    step2Title.innerText = 'Detail Produk (Hewan Hidup)';
                } else {
                    btnGoods.className = "py-2.5 px-4 rounded-xl text-xs font-bold border transition-all bg-emerald-500 text-white border-emerald-500 shadow-sm";
                    btnAnimal.className = "py-2.5 px-4 rounded-xl text-xs font-bold border transition-all bg-white text-slate-600 border-slate-200 hover:bg-slate-50";
                    animalFields.classList.add('hidden');
                    badge.innerText = 'Barang';
                    badge.className = 'px-2.5 py-1 bg-blue-100 text-blue-700 font-bold text-[10px] rounded-md';
                    step2Title.innerText = 'Detail Produk (Barang / Aksesoris)';
                }
            }

            function validateAndGoStep(targetStep) {
                if (targetStep === 2) {
                    const name = document.getElementById('inputName').value.trim();
                    const price = document.getElementById('inputPrice').value.trim();
                    const stock = document.getElementById('inputStock').value.trim();

                    let valid = true;
                    if (!name) {
                        document.getElementById('errName').classList.remove('hidden');
                        valid = false;
                    } else {
                        document.getElementById('errName').classList.add('hidden');
                    }

                    if (!price || parseFloat(price) < 0) {
                        document.getElementById('errPrice').classList.remove('hidden');
                        valid = false;
                    } else {
                        document.getElementById('errPrice').classList.add('hidden');
                    }

                    if (!stock || parseInt(stock) < 0) {
                        document.getElementById('errStock').classList.remove('hidden');
                        valid = false;
                    } else {
                        document.getElementById('errStock').classList.add('hidden');
                    }

                    if (!valid) return;
                }

                goToStep(targetStep);
            }

            function goToStep(step) {
                currentStep = step;
                document.getElementById('step1Content').classList.add('hidden');
                document.getElementById('step2Content').classList.add('hidden');
                document.getElementById('step3Content').classList.add('hidden');

                document.getElementById('step' + step + 'Content').classList.remove('hidden');

                // Highlight Nav Indicators
                for(let i=1; i<=3; i++) {
                    const nav = document.getElementById('step' + i + 'Nav');
                    const badge = document.getElementById('badge' + i);
                    const line = document.getElementById('line' + i);

                    if(i <= step) {
                        nav.className = "flex items-center gap-2 text-emerald-600 font-bold whitespace-nowrap";
                        badge.className = "w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs";
                        if(line) line.className = "h-0.5 w-12 bg-emerald-500";
                    } else {
                        nav.className = "flex items-center gap-2 text-slate-400 font-medium whitespace-nowrap";
                        badge.className = "w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs";
                        if(line) line.className = "h-0.5 w-12 bg-slate-200";
                    }
                }
            }

            function updatePreview() {
                const name = document.getElementById('inputName').value.trim() || 'Kucing Persian';
                const priceVal = document.getElementById('inputPrice').value;
                const stockVal = document.getElementById('inputStock').value;

                const price = priceVal ? parseInt(priceVal).toLocaleString('id-ID') : '0';
                const stock = stockVal !== '' ? stockVal : '0';

                document.getElementById('cardPreviewTitle').innerText = name;
                document.getElementById('cardPreviewPrice').innerText = 'Rp ' + price;
                document.getElementById('cardPreviewStock').innerText = 'Stok: ' + stock;
            }

            function previewImage(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('imgPreview').src = e.target.result;
                        document.getElementById('imagePreviewContainer').classList.remove('hidden');

                        const cardImg = document.getElementById('cardPreviewImg');
                        cardImg.src = e.target.result;
                        cardImg.classList.remove('hidden');
                        document.getElementById('cardPreviewPlaceholder').classList.add('hidden');
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Inisialisasi awal saat halaman dimuat
            document.addEventListener('DOMContentLoaded', function() {
                updatePreview();
                setJenisProduk("{{ old('type', 'animal') }}");
            });
        </script>
    @endif
</x-layouts.tenant>
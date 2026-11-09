<x-layouts.tenant :tenant="$tenant" title="Edit Produk">
    <div class="mb-6 flex items-center justify-between max-w-3xl">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Edit Produk</h2>
            <p class="text-xs text-slate-500 mt-1">Ubah data produk {{ $product->name }}.</p>
        </div>
        <a href="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id]) }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">← Kembali</a>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm max-w-3xl">
        <form method="POST" action="{{ route('tenant.products.update', ['tenant' => $tenant->slug ?? $tenant->id, 'product' => $product->id]) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Nama Produk -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required 
                       class="w-full px-3.5 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <!-- Grid Kategori & Tipe -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full px-3.5 py-2 text-xs border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Produk *</label>
                    <select id="product-type" name="type" required class="w-full px-3.5 py-2 text-xs border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="animal" {{ old('type', $product->type) == 'animal' ? 'selected' : '' }}>Hewan Hidup</option>
                        <option value="goods" {{ old('type', $product->type) == 'goods' ? 'selected' : '' }}>Barang / Produk Fisik</option>
                    </select>
                </div>
            </div>

            <!-- Grid Harga & Stok -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Harga (Rp) *</label>
                    <input type="number" name="base_price" value="{{ old('base_price', (int)$product->base_price) }}" required min="0"
                           class="w-full px-3.5 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @error('base_price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Tersedia *</label>
                    <input type="number" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty) }}" required min="0"
                           class="w-full px-3.5 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @error('stock_qty') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Detail Spesifik Hewan (Otomatis Tampil Jika Tipe = Animal) -->
            <div id="animal-fields" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4 {{ old('type', $product->type) === 'animal' ? '' : 'hidden' }}">
                <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                    <span>🐾</span> Detail Spesifikasi Hewan
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ras / Breed</label>
                        <input type="text" name="breed" value="{{ old('breed', $product->breed ?? '') }}" placeholder="Contoh: Persian, Cupang Halfmoon"
                               class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Usia (Bulan)</label>
                        <input type="number" name="age_months" value="{{ old('age_months', $product->age_months ?? '') }}" min="0"
                               class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Kelamin</label>
                        <select name="gender" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-white">
                            <option value="">-- Pilih --</option>
                            <option value="male" {{ old('gender', $product->gender ?? '') == 'male' ? 'selected' : '' }}>Jantan</option>
                            <option value="female" {{ old('gender', $product->gender ?? '') == 'female' ? 'selected' : '' }}>Betina</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Status Vaksinasi</label>
                        <input type="text" name="vaccination_status" value="{{ old('vaccination_status', $product->vaccination_status ?? '') }}" placeholder="Contoh: Sudah Vaksin / Belum"
                               class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Berat (Kg)</label>
                        <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $product->weight_kg ?? '') }}"
                               class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Riwayat Kesehatan</label>
                    <textarea name="health_history" rows="2" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-white">{{ old('health_history', $product->health_history ?? '') }}</textarea>
                </div>
            </div>

            <!-- Deskripsi -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-3.5 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">{{ old('description', $product->description) }}</textarea>
            </div>

            <!-- Foto Produk -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Produk</label>
                @if($product->photo_path)
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ asset('storage/' . $product->photo_path) }}" class="w-12 h-12 rounded-lg object-cover border border-slate-200">
                        <span class="text-[11px] text-slate-400">Foto saat ini</span>
                    </div>
                @endif
                <input type="file" name="photo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            </div>

            <!-- Status Aktif -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_available" id="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="is_available" class="text-xs text-slate-700 font-medium">Tampilkan produk di katalog (Aktif)</label>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id]) }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-xs font-semibold text-white shadow-sm transition-all">
                    Update Produk
                </button>
            </div>
        </form>
    </div>

    <!-- Script Dynamic Toggle Detail Hewan -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('product-type');
            const animalFields = document.getElementById('animal-fields');

            typeSelect.addEventListener('change', function() {
                if (this.value === 'animal') {
                    animalFields.classList.remove('hidden');
                } else {
                    animalFields.classList.add('hidden');
                }
            });
        });
    </script>
</x-layouts.tenant>
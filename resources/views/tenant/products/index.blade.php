<x-layouts.tenant :tenant="$tenant" title="Produk Saya">
    <!-- Header Page -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Produk Saya</h2>
            <p class="text-xs text-slate-500 mt-1 font-medium">Kelola produk toko Anda</p>
        </div>
        <a href="{{ route('tenant.products.create', ['tenant' => $tenant->slug ?? $tenant->id]) }}" 
           class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs px-5 py-3 rounded-xl transition-all shadow-md shadow-emerald-500/20 active:scale-95">
            <span class="text-base font-bold">+</span>
            <span>Tambah Produk</span>
        </a>
    </div>

    <!-- Tab Filter Jenis Produk (Semua, Hewan, Barang) -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 mb-5 overflow-x-auto">
        <a href="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id]) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ !$type ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Semua ({{ $countSemua }})
        </a>
        <a href="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id, 'type' => 'animal']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $type === 'animal' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Hewan ({{ $countHewan }})
        </a>
        <a href="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id, 'type' => 'goods']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $type === 'goods' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Barang ({{ $countBarang }})
        </a>
    </div>

    <!-- Bar Filter (Dropdown & Search) -->
    <form method="GET" action="{{ route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id]) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @if($type) <input type="hidden" name="type" value="{{ $type }}"> @endif
        
        <!-- Filter Kategori -->
        <select name="category_id" onchange="this.form.submit()" class="px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm">
            <option value="">Kategori (Semua)</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>

        <!-- Filter Harga -->
        <select name="price" onchange="this.form.submit()" class="px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm">
            <option value="">Harga</option>
            <option value="asc" {{ $priceFilter === 'asc' ? 'selected' : '' }}>Terendah → Tertinggi</option>
            <option value="desc" {{ $priceFilter === 'desc' ? 'selected' : '' }}>Tertinggi → Terendah</option>
        </select>

        <!-- Filter Stok -->
        <select name="stock" onchange="this.form.submit()" class="px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm">
            <option value="">Stok</option>
            <option value="available" {{ $stockFilter === 'available' ? 'selected' : '' }}>Tersedia</option>
            <option value="low" {{ $stockFilter === 'low' ? 'selected' : '' }}>Stok Menipis (≤ 3)</option>
        </select>

        <!-- Search Input -->
        <div class="relative">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari Produk..." 
                   class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </form>

    <!-- Grid Card Produk (4 Kolom Sesuai Figma) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse($products as $product)
            <div class="bg-white border border-slate-100 rounded-2xl p-3 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
                <div>
                    <!-- Container Gambar Produk -->
                    <div class="relative w-full h-40 rounded-xl overflow-hidden bg-slate-100 mb-3">
                        @if($product->photo_path)
                            <img src="{{ asset('storage/' . $product->photo_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-300 font-bold text-2xl">🐾</div>
                        @endif
                    </div>

                    <!-- Judul & Subtitle -->
                    <h3 class="font-bold text-slate-800 text-sm truncate">{{ $product->name }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                        @if($product->type === 'animal')
                            {{ $product->breed ?? 'Ras Umum' }} {{ $product->age_months ? '| Usia '.$product->age_months.' Bulan' : '' }}
                        @else
                            {{ $product->category_name ?? 'Barang / Aksesoris' }}
                        @endif
                    </p>

                    <!-- Harga Produk -->
                    <div class="font-extrabold text-slate-900 text-sm mt-2">
                        Rp {{ number_format($product->base_price, 0, ',', '.') }}
                    </div>
                </div>

                <!-- Footer Card (Radius, Badge Tipe, Stok, Button Edit) -->
                <div class="pt-3 border-t border-slate-50 mt-3 flex items-center justify-between text-[10px]">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1">
    📍 {{ (float) ($tenant->max_delivery_radius_km ?? 15) }} km
</span>
                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-md font-semibold {{ $product->type === 'animal' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-blue-50 text-blue-600 border border-blue-100' }}">
                            {{ $product->type === 'animal' ? 'Hewan' : 'Barang' }}
                        </span>
                        <span class="text-slate-500 font-semibold">Stok: {{ $product->stock_qty }}</span>
                        <a href="{{ route('tenant.products.edit', ['tenant' => $tenant->slug ?? $tenant->id, 'product' => $product->id]) }}" 
                           class="p-1 text-slate-400 hover:text-emerald-600 transition-colors">
                            ✏️
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-100">
                <div class="text-4xl mb-2">📦</div>
                <h4 class="font-bold text-slate-700 text-sm">Belum Ada Produk</h4>
                <p class="text-xs text-slate-400 mt-1">Silakan klik tombol "+ Tambah Produk" di atas.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $products->links() }}
    </div>
</x-layouts.tenant>
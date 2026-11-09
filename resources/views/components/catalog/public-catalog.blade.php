
<?php

use App\Models\ProductCategory;
use App\Modules\Catalog\Services\PublicCatalogQuery;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max', except: '')]
    public string $maxPrice = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'type', 'categoryId', 'minPrice', 'maxPrice']);
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        $min = $this->minPrice !== '' && is_numeric($this->minPrice)
            ? max(0, (int) $this->minPrice)
            : null;

        $max = $this->maxPrice !== '' && is_numeric($this->maxPrice)
            ? max(0, (int) $this->maxPrice)
            : null;

        return app(PublicCatalogQuery::class)->browse(
            search: $this->search !== '' ? $this->search : null,
            type: in_array($this->type, ['animal', 'good'], true) ? $this->type : null,
            categoryId: $this->categoryId !== '' && ctype_digit($this->categoryId)
                ? (int) $this->categoryId
                : null,
            minPrice: $min,
            maxPrice: $max,
        );
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::query()
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->whereHas('tenant', function (Builder $query): void {
                $query->where('status', 'active');
            })
            ->orderBy('name')
            ->get(['id', 'tenant_id', 'name']);
    }
};
?>

<div>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-emerald-50 px-6 py-10 sm:px-10 lg:py-14">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-emerald-700">
                    Selamat datang di PetMarket
                </p>

                <h1 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    Semua kebutuhan hewan kesayanganmu
                </h1>

                <p class="mt-4 text-base leading-7 text-zinc-600">
                    Temukan hewan peliharaan dan perlengkapannya dari berbagai toko dalam satu tempat.
                </p>

                <div class="mt-6">
                    <label for="catalog-search" class="sr-only">Cari produk</label>
                    <input
                        id="catalog-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama produk atau ras hewan..."
                        class="w-full rounded-xl border-0 bg-white px-5 py-4 text-zinc-900 shadow-sm ring-1 ring-zinc-200 placeholder:text-zinc-400 focus:ring-2 focus:ring-emerald-500"
                    >
                </div>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-[250px_minmax(0,1fr)]">
            <aside class="h-fit rounded-2xl border border-zinc-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-zinc-900">Filter produk</h2>

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="text-sm font-medium text-emerald-700 hover:text-emerald-900"
                    >
                        Reset
                    </button>
                </div>

                <div class="mt-6">
                    <label for="catalog-type" class="mb-2 block text-sm font-semibold text-zinc-700">
                        Jenis produk
                    </label>

                    <select
                        id="catalog-type"
                        wire:model.live="type"
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">Semua jenis</option>
                        <option value="animal">Hewan</option>
                        <option value="good">Perlengkapan</option>
                    </select>
                </div>

                <div class="mt-5">
                    <label for="catalog-category" class="mb-2 block text-sm font-semibold text-zinc-700">
                        Kategori
                    </label>

                    <select
                        id="catalog-category"
                        wire:model.live="categoryId"
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">Semua kategori</option>

                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-2 text-xs text-zinc-500">
                        Nama kategori dapat sama pada toko yang berbeda.
                    </p>
                </div>

                <div class="mt-5">
                    <p class="mb-2 text-sm font-semibold text-zinc-700">Rentang harga (Rp)</p>

                    <label for="catalog-min" class="sr-only">Harga minimum</label>
                    <input
                        id="catalog-min"
                        type="number"
                        min="0"
                        wire:model.live.debounce.400ms="minPrice"
                        placeholder="Harga minimum"
                        class="mb-3 w-full rounded-lg border-zinc-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >

                    <label for="catalog-max" class="sr-only">Harga maksimum</label>
                    <input
                        id="catalog-max"
                        type="number"
                        min="0"
                        wire:model.live.debounce.400ms="maxPrice"
                        placeholder="Harga maksimum"
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>
            </aside>

            <section>
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold text-zinc-900">Jelajahi produk</h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            Produk dari toko PetMarket yang aktif
                        </p>
                    </div>
                </div>

                <div wire:loading.delay class="mb-4 text-sm text-emerald-700">
                    Memuat produk...
                </div>

                @if ($this->products->isEmpty())
                    <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
                        <h3 class="text-lg font-semibold text-zinc-900">Produk tidak ditemukan</h3>
                        <p class="mt-2 text-sm text-zinc-500">
                            Coba ubah kata pencarian atau filter produkmu.
                        </p>
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="mt-5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                        >
                            Hapus semua filter
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->products as $product)
                            <article wire:key="product-{{ $product->tenant_id }}-{{ $product->id }}"
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:-translate-y-1 hover:shadow-lg"
                            >
                                <div class="aspect-[4/3] bg-zinc-100">
                                    @if ($product->photo_path)
                                        <img
                                            src="{{ asset('storage/'.$product->photo_path) }}"
                                            alt="{{ $product->name }}"
                                            loading="lazy"
                                            class="h-full w-full object-cover"
                                        >
                                    @else
                                        <div class="flex h-full items-center justify-center text-sm text-zinc-400">
                                            Foto belum tersedia
                                        </div>
                                    @endif
                                </div>

                                <div class="p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            {{ $product->type === 'animal' ? 'Hewan' : 'Perlengkapan' }}
                                        </span>

                                        @if ($product->category)
                                            <span class="text-xs text-zinc-500">
                                                {{ $product->category->name }}
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="mt-3 line-clamp-2 font-semibold text-zinc-900">
                                        {{ $product->name }}
                                    </h3>

                                    @if ($product->type === 'animal' && $product->animalDetail)
                                        <p class="mt-1 text-sm text-zinc-500">
                                            Ras: {{ $product->animalDetail->breed }}
                                        </p>
                                    @endif

                                    <p class="mt-3 text-lg font-bold text-emerald-700">
                                        Rp{{ number_format($product->base_price, 0, ',', '.') }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        Stok: {{ $product->stock_qty }}
                                    </p>

                                    <p class="mt-3 text-sm leading-6 text-zinc-600">
                                        {{ \Illuminate\Support\Str::limit($product->description ?? 'Tidak ada deskripsi.', 90) }}
                                    </p>

                                    <div class="mt-4 border-t border-zinc-100 pt-3">
                                        <p class="text-xs text-zinc-500">Dijual oleh</p>
                                        <p class="mt-1 font-medium text-zinc-800">
                                            {{ $product->tenant->name }}
                                        </p>

                                        <a
                                            href="{{ route('customer.home', ['shop' => $product->tenant->slug]) }}"
                                            class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                                        >
                                            Kunjungi toko
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </section>
        </div>
    </section>
</div>

<?php

use App\Models\Product;
use App\Models\Tenant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Katalog publik toko PetMarket.
 * Memuat ulang tiap 5 detik untuk menyinkronkan ketersediaan dan stok produk secara real-time.
 */
new class extends Component
{
    public string $marketSlug = '';

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $categoryId = null;

    public function mount(string $marketSlug = ''): void
    {
        $this->marketSlug = $marketSlug;
    }

    public function filterCategory(?int $id): void
    {
        $this->categoryId = $id;
    }

    /**
     * Meneruskan permintaan tambah ke komponen keranjang (ordering::cart).
     */
    public function add(int $productId): void
    {
        $this->dispatch('cart-add', productId: $productId);
    }

    #[Computed]
    public function tenant(): ?Tenant
    {
        return Tenant::query()
            ->where('slug', $this->marketSlug)
            ->where('status', 'active')
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    #[Computed]
    public function products()
    {
        $tenant = $this->tenant();
        if (! $tenant) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Product::query()
            ->where('tenant_id', $tenant->id)
            ->with('category')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->categoryId !== null, function ($query): void {
                $query->where('category_id', $this->categoryId);
            })
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Category>
     */
    #[Computed]
    public function categories()
    {
        return $this->products()
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->values();
    }
};
?>

<div class="space-y-4" wire:poll.5s>
    @if (! $this->tenant)
        <x-empty-state title="Toko tidak ditemukan" description="Periksa kembali tautan toko atau pindai QR untuk membuka katalog." />
    @else
        <div class="flex flex-col gap-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari hewan peliharaan, pakan, atau aksesori…"
                class="min-h-11 rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-600 dark:bg-zinc-800" />

            @if ($this->categories->isNotEmpty())
                <div class="flex gap-2 overflow-x-auto pb-1" data-test="category-chips">
                    <button type="button" wire:click="filterCategory(null)"
                        @class(['min-h-9 shrink-0 rounded-full border px-3 text-sm font-medium', 'border-red-600 bg-red-600 text-white' => $categoryId === null, 'border-zinc-300 dark:border-zinc-600' => $categoryId !== null])>
                        Semua
                    </button>
                    @foreach ($this->categories as $cat)
                        <button type="button" wire:key="chip-{{ $cat->id }}" wire:click="filterCategory({{ $cat->id }})"
                            @class(['min-h-9 shrink-0 rounded-full border px-3 text-sm font-medium', 'border-red-600 bg-red-600 text-white' => $categoryId === $cat->id, 'border-zinc-300 dark:border-zinc-600' => $categoryId !== $cat->id])>
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($this->products->isEmpty())
            <x-empty-state title="Produk tidak ditemukan" description="Coba kata kunci atau kategori lain." />
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->products as $product)
                    @php($sellable = $product->is_available && $product->stock_qty > 0)
                    <div wire:key="product-{{ $product->id }}"
                        @class(['flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900', 'opacity-60' => ! $sellable])
                        data-sellable="{{ $sellable ? '1' : '0' }}">
                        <div>
                            <div class="mb-3 flex h-32 items-center justify-center rounded-lg bg-zinc-100 text-xs font-medium text-zinc-400 dark:bg-zinc-800">
                                Tidak ada gambar
                            </div>

                            <h3 class="font-bold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</h3>

                            @if ($product->category)
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $product->category->name }}</p>
                            @endif

                            <p class="mt-2 text-sm font-extrabold text-emerald-600">
                                Rp{{ number_format($product->base_price, 0, ',', '.') }}
                            </p>

                            <p class="mt-0.5 text-xs text-zinc-500">
                                Stok: {{ $product->stock_qty }}
                            </p>

                            @if ($product->description)
                                <p class="mt-2 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $product->description }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-4">
                            @if ($sellable)
                                <button type="button" wire:click="add({{ $product->id }})"
                                    class="min-h-9 w-full rounded-lg bg-zinc-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                                    + Tambah ke Keranjang
                                </button>
                            @else
                                <span class="block w-full rounded-lg border border-zinc-300 py-1.5 text-center text-xs font-bold text-zinc-500">
                                    HABIS
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
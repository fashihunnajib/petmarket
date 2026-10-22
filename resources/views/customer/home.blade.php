
<x-layouts.customer :title="'Toko: '.$shop">
    @if ($products->isEmpty())
        <x-empty-state
            title="Belum ada produk tersedia"
            description="Produk yang tersedia di toko ini akan muncul di sini."
        />
    @else
        <div class="mb-4">
            <h1 class="text-lg font-bold">{{ $shop }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Pilih produk yang ingin kamu lihat.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach ($products as $product)
                <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                    @if ($product->photo_path)
                        <img
                            src="{{ asset('storage/'.$product->photo_path) }}"
                            alt="{{ $product->name }}"
                            class="h-32 w-full object-cover"
                        >
                    @else
                        <div class="flex h-32 items-center justify-center bg-zinc-100 text-sm text-zinc-400 dark:bg-zinc-800">
                            Tidak ada gambar
                        </div>
                    @endif

                    <div class="p-3">
                        <h2 class="font-semibold">{{ $product->name }}</h2>

                        @if ($product->category)
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $product->category->name }}
                            </p>
                        @endif

                        <p class="mt-2 font-bold text-emerald-600">
                            Rp{{ number_format($product->base_price, 0, ',', '.') }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Stok: {{ $product->stock_qty }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.customer>

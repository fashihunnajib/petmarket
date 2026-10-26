<x-layouts.customer :title="'Toko: '.$shop">
    <main class="mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-2xl font-bold">
            Katalog {{ $shop }}
        </h1>

        <p class="mt-2 text-gray-600 dark:text-zinc-400">
            Temukan produk yang tersedia di toko ini.
        </p>

        @if ($products->isEmpty())
            <div class="mt-6">
                <x-empty-state
                    title="Belum ada produk tersedia"
                    description="Produk yang tersedia di toko ini akan muncul di sini."
                />
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        @if ($product->photo_path)
                            <img
                                src="{{ asset('storage/'.$product->photo_path) }}"
                                alt="{{ $product->name }}"
                                class="h-40 w-full object-cover"
                            >
                        @else
                            <div class="flex h-40 items-center justify-center bg-zinc-100 text-sm text-zinc-400 dark:bg-zinc-800">
                                Tidak ada gambar
                            </div>
                        @endif

                        <div class="p-5">
                            <h2 class="text-lg font-semibold">
                                {{ $product->name }}
                            </h2>

                            @if ($product->category)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $product->category->name }}
                                </p>
                            @endif

                            <p class="mt-3 text-lg font-bold text-emerald-600">
                                Rp{{ number_format($product->base_price, 0, ',', '.') }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Stok: {{ $product->stock_qty }}
                            </p>

                            @if ($product->description)
                                <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $product->description }}
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</x-layouts.customer>
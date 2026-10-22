
<x-layouts.customer :title="'Toko: '.$shop">
    <main class="mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-2xl font-bold">
            Katalog {{ $shop }}
        </h1>

        <p class="mt-2 text-gray-600">
            Temukan produk yang tersedia di toko ini.
        </p>

        @if ($products->isEmpty())
            <div class="mt-6 rounded-lg border p-6">
                <h2 class="font-semibold">Belum ada produk tersedia</h2>
                <p class="mt-2 text-gray-600">
                    Produk yang tersedia akan ditampilkan di sini.
                </p>
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <article class="rounded-lg border p-5">
                        <h2 class="text-lg font-semibold">
                            {{ $product->name }}
                        </h2>

                        @if ($product->category)
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $product->category->name }}
                            </p>
                        @endif

                        <p class="mt-3 text-lg font-bold">
                            Rp {{ number_format($product->base_price, 0, ',', '.') }}
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Stok: {{ $product->stock_qty }}
                        </p>

                        @if ($product->description)
                            <p class="mt-3 text-sm text-gray-700">
                                {{ $product->description }}
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</x-layouts.customer>

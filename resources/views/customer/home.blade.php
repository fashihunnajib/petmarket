<x-layouts.customer :title="'Toko: '.$shop">
    <div x-data="{ cartOpen: false }" @open-cart.window="cartOpen = true" class="w-full">
        <main class="mx-auto w-full max-w-6xl px-4 py-8 pb-24 sm:px-6">
            <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
                <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Katalog {{ $shop }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Temukan produk yang tersedia di toko ini.
                </p>
            </div>

            @if ($products->isEmpty())
                <div class="mt-8">
                    <x-empty-state
                        title="Belum ada produk tersedia"
                        description="Produk yang tersedia di toko ini akan muncul di sini."
                    />
                </div>
            @else
                <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <article class="flex min-w-0 flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                @if ($product->photo_path)
                                    <img
                                        src="{{ asset('storage/'.$product->photo_path) }}"
                                        alt="{{ $product->name }}"
                                        class="h-44 w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-44 items-center justify-center bg-zinc-100 text-sm font-medium text-zinc-400 dark:bg-zinc-800">
                                        Tidak ada gambar
                                    </div>
                                @endif

                                <div class="p-5">
                                    @if ($product->category)
                                        <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                            {{ $product->category->name }}
                                        </span>
                                    @endif

                                    <h2 class="mt-2 text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                        {{ $product->name }}
                                    </h2>

                                    <div class="mt-3 flex flex-wrap items-baseline justify-between gap-2">
                                        <p class="whitespace-nowrap text-lg font-extrabold text-emerald-600">
                                            Rp{{ number_format($product->base_price, 0, ',', '.') }}
                                        </p>
                                        <span class="whitespace-nowrap text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Stok: {{ $product->stock_qty }}
                                        </span>
                                    </div>

                                    @if ($product->description)
                                        <p class="mt-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $product->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="px-5 pb-5 pt-2">
                                <button
                                    type="button"
                                    onclick="Livewire.dispatch('cart-add', { productId: {{ $product->id }} }); window.dispatchEvent(new CustomEvent('open-cart'));"
                                    class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.99]"
                                >
                                    + Tambah ke Keranjang
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </main>

        {{-- Satu Tombol Keranjang Mengambang (Pojok Kanan Bawah) --}}
        <button
            type="button"
            @click="cartOpen = true"
            class="fixed bottom-6 right-6 z-40 flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg transition hover:bg-emerald-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Lihat Keranjang</span>
        </button>

        {{-- Drawer Keranjang --}}
        <div
            x-show="cartOpen"
            x-cloak
            class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs"
            @click.self="cartOpen = false"
            @keydown.escape.window="cartOpen = false"
        >
            <div class="relative h-full w-full max-w-md overflow-y-auto bg-white p-4 shadow-2xl dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Ringkasan Pesanan</span>
                    <button
                        type="button"
                        @click="cartOpen = false"
                        class="rounded-lg border border-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Tutup ✕
                    </button>
                </div>

                <livewire:ordering::cart :marketSlug="$marketSlug ?? 'demo'" />
            </div>
        </div>
    </div>
</x-layouts.customer>
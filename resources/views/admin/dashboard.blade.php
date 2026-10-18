
<x-layouts.admin title="Dashboard">
    <h1 class="text-2xl font-semibold">Dashboard Pengelola</h1>
    <p class="mt-1 text-sm text-zinc-500">
        Ringkasan data PetMarket.
    </p>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Total Tenant</p>
            <p class="mt-2 text-3xl font-bold">{{ $totalTenants }}</p>
            <p class="mt-1 text-sm text-emerald-600">
                {{ $activeTenants }} tenant aktif
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Total Produk</p>
            <p class="mt-2 text-3xl font-bold">{{ $totalProducts }}</p>
            <p class="mt-1 text-sm text-zinc-500">
                {{ $availableProducts }} produk tersedia
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Total Pesanan Tenant</p>
            <p class="mt-2 text-3xl font-bold">{{ $totalOrders }}</p>
            <p class="mt-1 text-sm text-zinc-500">
                Seluruh pesanan yang tercatat
            </p>
        </div>
    </div>
</x-layouts.admin>

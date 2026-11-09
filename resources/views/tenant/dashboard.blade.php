<x-layouts.tenant :tenant="$tenant" title="Dashboard Tenant">
    <!-- Header Dashboard -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Dashboard Tenant</h2>
            <p class="text-xs text-slate-500 mt-1">Selamat datang di panel toko Anda. Berikut ringkasan aktivitas hari ini.</p>
        </div>
        <div class="bg-white px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 shadow-sm">
            📅 {{ now()->translatedFormat('l, d F Y') }}
        </div>
    </div>

    <!-- 1. 4 CARD KPI STATISTIK -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <!-- Card 1: Total Pesanan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400">Total Pesanan</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($totalOrders) }}</h3>
                <span class="text-xs text-emerald-500 font-semibold flex items-center gap-1 mt-1">
                    ↑ +20% <span class="text-slate-400 font-normal">dari kemarin</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold">🛒</div>
        </div>

        <!-- Card 2: Pendapatan Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400">Pendapatan Hari Ini</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($incomeToday, 0, ',', '.') }}</h3>
                <span class="text-xs text-emerald-500 font-semibold flex items-center gap-1 mt-1">
                    ↑ +15% <span class="text-slate-400 font-normal">dari kemarin</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold">💲</div>
        </div>

        <!-- Card 3: Saldo Toko -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400">Saldo Toko</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($storeBalance, 0, ',', '.') }}</h3>
                <a href="#" class="inline-block mt-2 text-[11px] bg-emerald-500 hover:bg-emerald-600 text-white font-medium px-3 py-1 rounded-full transition-colors">
                    Tarik Dana
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold">👛</div>
        </div>

        <!-- Card 4: Produk Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400">Produk Aktif</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($activeProductsCount) }}</h3>
                <span class="text-xs text-emerald-500 font-semibold flex items-center gap-1 mt-1">
                    +2 <span class="text-slate-400 font-normal">produk baru</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold">📦</div>
        </div>
    </div>

    <!-- 2. TABEL PESANAN TERBARU & GRAFIK -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tabel Pesanan Terbaru -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Pesanan Terbaru</h3>
                <a href="#" class="text-xs font-semibold text-emerald-500 hover:underline">Lihat Semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                            <th class="pb-3 font-semibold">ID Pesanan</th>
                            <th class="pb-3 font-semibold">Produk</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold">Harga</th>
                            <th class="pb-3 font-semibold">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 font-medium text-slate-700">
                        <tr>
                            <td class="py-3 font-bold text-slate-800">#ORD-952A</td>
                            <td class="py-3">Kalung Perintis (Hewan)</td>
                            <td class="py-3"><span class="px-2.5 py-1 rounded-full text-[10px] bg-emerald-100 text-emerald-700 font-semibold">Pesanan Baru</span></td>
                            <td class="py-3">Rp 120.000</td>
                            <td class="py-3 text-slate-400">10:24</td>
                        </tr>
                        <tr>
                            <td class="py-3 font-bold text-slate-800">#ORD-951B</td>
                            <td class="py-3">Pakan Burung (Kuning)</td>
                            <td class="py-3"><span class="px-2.5 py-1 rounded-full text-[10px] bg-amber-100 text-amber-700 font-semibold">Diproses</span></td>
                            <td class="py-3">Rp 125.000</td>
                            <td class="py-3 text-slate-400">09:47</td>
                        </tr>
                        <tr>
                            <td class="py-3 font-bold text-slate-800">#ORD-950C</td>
                            <td class="py-3">Kandang Kucing (Bening)</td>
                            <td class="py-3"><span class="px-2.5 py-1 rounded-full text-[10px] bg-blue-100 text-blue-700 font-semibold">Kemitraan</span></td>
                            <td class="py-3">Rp 350.000</td>
                            <td class="py-3 text-slate-400">08:33</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar Right: Ringkasan Grafik -->
        <div class="flex flex-col gap-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="font-bold text-slate-800 mb-2">Grafik Pendapatan</h3>
                <p class="text-xs text-slate-400">Pendapatan stabil dalam 7 hari terakhir.</p>
            </div>
        </div>
    </div>
</x-layouts.tenant>
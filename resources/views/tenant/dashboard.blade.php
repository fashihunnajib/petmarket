<x-layouts.tenant :tenant="$tenant" title="Dashboard Tenant">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
        $statusBadges = [
            'pending_payment' => ['Menunggu Bayar', 'bg-amber-100 text-amber-700'],
            'new_order' => ['Pesanan Baru', 'bg-emerald-100 text-emerald-700'],
            'processing' => ['Diproses', 'bg-blue-100 text-blue-700'],
            'in_transit' => ['Dikirim', 'bg-indigo-100 text-indigo-700'],
            'completed' => ['Selesai', 'bg-slate-100 text-slate-700'],
            'cancelled' => ['Dibatalkan', 'bg-red-100 text-red-700'],
            'expired' => ['Kedaluwarsa', 'bg-red-100 text-red-700'],
        ];
    @endphp

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
                <p class="mt-1 text-xs font-medium {{ $totalOrders > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $totalOrders > 0 ? $totalOrders . ' pesanan tercatat' : 'Belum ada pesanan masuk' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold shrink-0">🛒</div>
        </div>

        <!-- Card 2: Pendapatan Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400">Pendapatan Hari Ini</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($incomeToday, 0, ',', '.') }}</h3>
                <p class="mt-1 text-xs font-medium {{ $incomeToday > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $incomeToday > 0 ? '↑ Pemasukan aktif hari ini' : 'Belum ada transaksi hari ini' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold shrink-0">💲</div>
        </div>

        <!-- Card 3: Saldo Toko -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <span class="text-xs font-semibold text-slate-400">Saldo Toko</span>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">
                        Rp {{ number_format($storeBalance ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold shrink-0">
                    👛
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('tenant.withdrawals.index', ['tenant' => $tenantSlug]) }}"
                   class="inline-flex items-center justify-center px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-sm transition-all active:scale-95">
                    Tarik Dana
                </a>
            </div>
        </div>

        <!-- Card 4: Produk Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400">Produk Aktif</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($activeProductsCount) }}</h3>
                <p class="mt-1 text-xs font-medium {{ $activeProductsCount > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $activeProductsCount > 0 ? $activeProductsCount . ' produk siap dijual' : 'Belum ada produk aktif' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold shrink-0">📦</div>
        </div>
    </div>

    <!-- 2. TABEL PESANAN TERBARU & GRAFIK -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tabel Pesanan Terbaru -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Pesanan Terbaru</h3>
                <a href="{{ route('tenant.orders.index', ['tenant' => $tenantSlug]) }}" class="text-xs font-semibold text-emerald-500 hover:underline">
                    Lihat Semua
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                            <th class="pb-3 font-semibold">ID Pesanan</th>
                            <th class="pb-3 font-semibold">Pelanggan</th>
                            <th class="pb-3 font-semibold">Produk</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold">Total</th>
                            <th class="pb-3 font-semibold">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($recentOrders as $order)
                            @php
                                $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan';
                                $badge = $statusBadges[$order->status ?? ''] ?? [ucfirst(str_replace('_', ' ', $order->status ?? 'Baru')), 'bg-emerald-100 text-emerald-700'];
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="py-3.5 pr-3 font-semibold text-gray-900 whitespace-nowrap">
                                    <a href="{{ route('tenant.orders.show', ['tenant' => $tenantSlug, 'order' => $order->id]) }}" class="hover:text-emerald-600 hover:underline">
                                        {{ $order->order_number ?? ('#ORD-' . $order->id) }}
                                    </a>
                                </td>
                                <td class="py-3.5 pr-3 text-gray-700">
                                    {{ $customerName }}
                                </td>
                                <td class="py-3.5 pr-3 text-gray-700">
                                    @if($order->items->isNotEmpty())
                                        <span class="font-medium text-gray-800">
                                            {{ $order->items->map(fn ($item) => $item->name_snapshot . ' (' . $item->quantity . 'x)')->implode(', ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[1] }}">
                                        {{ $badge[0] }}
                                    </span>
                                </td>
                                <td class="py-3.5 pr-3 font-semibold text-gray-900 whitespace-nowrap">
                                    Rp {{ number_format((float) ($order->grand_total_amount ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 text-gray-400 whitespace-nowrap">
                                    {{ $order->created_at?->format('H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-gray-400">
                                    Belum ada pesanan masuk untuk toko ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        

        <!-- Card Grafik Pendapatan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Grafik Pendapatan</h3>
                <p class="text-xs text-slate-400 mt-0.5 mb-4">Pendapatan stabil dalam 7 hari terakhir.</p>
            </div>
            <div class="h-56 relative w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
   <!-- 3. CARD REKAP TOTAL PENDAPATAN KOTOR -->
    <div class="mt-6 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-400">Total Semua Pendapatan Kotor</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1">
                Rp {{ number_format($totalGrossRevenue ?? 0, 0, ',', '.') }}
            </h3>
            <p class="mt-1 text-xs font-medium {{ ($totalGrossRevenue ?? 0) > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                Akumulasi total tagihan dari seluruh pesanan berstatus selesai
            </p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold shrink-0">
            💰
        </div>
    </div>

    <!-- Script Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Pendapatan',
                        data: @json($chartData),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                },
                                font: { size: 10 }
                            },
                            grid: { borderDash: [4, 4] }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>
</x-layouts.tenant>
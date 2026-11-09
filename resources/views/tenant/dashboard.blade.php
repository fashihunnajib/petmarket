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
            <tbody class="divide-y divide-slate-100 text-xs">
    @forelse($recentOrders as $order)
        <tr>
            <td class="py-3 px-4 font-bold">#{{ $order->order_number ?? $order->id }}</td>
            <td class="py-3 px-4">{{ $order->product_name ?? 'Produk' }}</td>
            <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold
                    {{ $order->status === 'new' ? 'bg-emerald-100 text-emerald-700' : '' }}
                    {{ $order->status === 'processing' ? 'bg-amber-100 text-amber-700' : '' }}
                    {{ $order->status === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}">
                    {{ ucfirst($order->status) }}
                </span>
            </td>
            <td class="py-3 px-4 font-semibold">Rp {{ number_format($order->grand_total_amount ?? 0, 0, ',', '.') }}</td>
            <td class="py-3 px-4 text-slate-400">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="py-4 text-center text-slate-400">Belum ada pesanan terbaru.</td>
        </tr>
    @endforelse
</tbody>
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

        <!-- Card Saldo Toko -->
<div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
    <div class="flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-slate-400">Saldo Toko</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1">
                Rp {{ number_format($storeBalance ?? 0, 0, ',', '.') }}
            </h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl font-bold">
            👛
        </div>
    </div>
    
    <div class="mt-4">
        <a href="{{ route('tenant.withdrawals.index', ['tenant' => $tenant->slug ?? $tenant->id]) }}" 
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
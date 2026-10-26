<x-layouts.admin title="Dashboard">
    <div class="space-y-6">
        <!-- Header Dashboard Ringkasan -->
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">Dashboard ringkasan</h2>
            <div class="h-[2px] w-full bg-emerald-400"></div>
        </div>

        <!-- 3 Stat Cards Row -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <!-- Total Tenant -->
            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Total Tenant
                </div>
                <div class="p-4">
                    <div class="text-5xl font-black text-gray-900">{{ $totalTenants ?? 0 }}</div>
                    <div class="mt-2 text-sm font-semibold text-gray-800">{{ $activeTenants ?? 0 }} Toko Aktif</div>
                </div>
            </div>

            <!-- TRX Hari ini -->
            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    TRX Hari ini
                </div>
                <div class="p-4">
                    <div class="text-5xl font-black text-gray-900">{{ $todayTransactions ?? $totalOrders ?? 128 }}</div>
                    <div class="mt-2 text-sm font-semibold text-gray-800">Berhasil</div>
                </div>
            </div>

            <!-- Pending -->
            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Pending
                </div>
                <div class="p-4">
                    <div class="text-5xl font-black text-gray-900">{{ $pendingOrders ?? 5 }}</div>
                    <div class="mt-2 text-sm font-semibold text-gray-800">Antrean</div>
                </div>
            </div>
        </div>

        <!-- Komisi Platform Banner Card -->
        <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
            <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                Komisi Platform
            </div>
            <div class="p-6">
                <div class="text-4xl font-black text-gray-900 sm:text-5xl">
                    Rp {{ isset($platformCommission) ? number_format($platformCommission, 0, ',', '.') : '4.520.000' }}
                </div>
            </div>
        </div>

        <!-- Grafik Transaksi Section -->
        <div class="pt-2">
            <h3 class="mb-4 text-lg font-bold text-gray-900">Grafik tranksaksi & Pendapatan 30 Hari terakhir</h3>
            <div class="rounded-sm border border-gray-300 bg-white p-4">
                <div class="h-64 w-full">
                    <canvas id="adminChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Chart.js -->
    @push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('adminChart');
        if (ctx) {
            new Chart(ctx, {
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            type: 'line',
                            label: 'Garis: Komisi (Rp)',
                            data: @json($chartCommissions),
                            borderColor: '#1e3a8a',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1'
                        },
                        {
                            type: 'bar',
                            label: 'Batang: Transaksi',
                            data: @json($chartTransactions),
                            backgroundColor: '#a7f3d0',
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: { display: true, text: 'Jumlah Transaksi' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Pendapatan (Rp)' }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
</x-layouts.admin>
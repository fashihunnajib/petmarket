<x-layouts.tenant title="Manajemen Pesanan" :tenant="$tenant">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
        $statusLabels = [
            'pending_payment' => ['Menunggu Bayar', 'bg-amber-100 text-amber-700'],
            'new_order' => ['Pesanan Baru', 'bg-emerald-100 text-emerald-700'],
            'processing' => ['Diproses', 'bg-blue-100 text-blue-700'],
            'in_transit' => ['Dikirim', 'bg-indigo-100 text-indigo-700'],
            'completed' => ['Selesai', 'bg-slate-100 text-slate-700'],
            'cancelled' => ['Dibatalkan', 'bg-red-100 text-red-700'],
            'expired' => ['Kedaluwarsa', 'bg-red-100 text-red-700'],
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Manajemen Pesanan</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola pesanan masuk, proses pengiriman, dan pantau transaksi toko Anda.</p>
            </div>

            <form method="GET" action="{{ route('tenant.orders.index', ['tenant' => $tenantSlug]) }}" class="flex items-center gap-2">
                @if($status !== 'all')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari no. pesanan / pembeli..."
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                    Cari
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <!-- Tab Filter Status -->
        <div class="flex flex-wrap gap-2">
            @php
                $tabs = [
                    'all' => 'Semua (' . $counts['all'] . ')',
                    'new_order' => 'Pesanan Baru (' . $counts['new_order'] . ')',
                    'processing' => 'Diproses (' . $counts['processing'] . ')',
                    'in_transit' => 'Dikirim (' . $counts['in_transit'] . ')',
                    'completed' => 'Selesai (' . $counts['completed'] . ')',
                    'cancelled' => 'Dibatalkan (' . $counts['cancelled'] . ')',
                ];
            @endphp

            @foreach($tabs as $key => $label)
                <a href="{{ route('tenant.orders.index', ['tenant' => $tenantSlug, 'status' => $key]) }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition {{ $status === $key ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- Tabel Pesanan -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/70 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3.5 whitespace-nowrap">No. Pesanan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Pelanggan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Item</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Pendapatan Bersih</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Status</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Waktu</th>
                            <th class="px-4 py-3.5 text-right whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            @php
                                $badge = $statusLabels[$order->status] ?? [ucfirst(str_replace('_', ' ', $order->status)), 'bg-slate-100 text-slate-700'];
                                $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-4 font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $order->order_number ?? ('#ORD-' . $order->id) }}
                                </td>
                                <td class="px-4 py-4 text-slate-700 whitespace-nowrap">
                                    {{ $customerName }}
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div class="font-medium text-slate-800 whitespace-nowrap">
                                        @foreach($order->items->take(2) as $item)
                                            <span>{{ $item->name_snapshot }} ({{ $item->quantity }}x)</span>@if(! $loop->last), @endif
                                        @endforeach
                                        @if($order->items->count() > 2)
                                            <span class="text-xs text-slate-400">+{{ $order->items->count() - 2 }} lainnya</span>
                                        @endif
                                    </div>
                                    <span class="block text-xs text-slate-400 whitespace-nowrap">
                                        {{ $order->items->count() }} jenis • Total {{ $order->items->sum('quantity') }} qty
                                    </span>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format((int) ($order->net_amount ?: $order->grand_total_amount), 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $badge[1] }}">
                                        {{ $badge[0] }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-400 text-xs whitespace-nowrap">
                                    {{ $order->created_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        @if($order->status === 'new_order')
                                            <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}" class="inline-flex m-0">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition whitespace-nowrap">
                                                    Terima
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('tenant.orders.show', ['tenant' => $tenantSlug, 'order' => $order]) }}"
                                           class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition whitespace-nowrap">
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                    Belum ada pesanan yang sesuai dengan filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.tenant>
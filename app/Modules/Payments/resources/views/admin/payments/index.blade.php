<x-layouts.admin title="Pembayaran QRIS">
    @php
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <h2 class="whitespace-nowrap text-xl font-bold text-gray-900">Manajemen Pembayaran QRIS</h2>
                <div class="h-[2px] w-full bg-emerald-400"></div>
            </div>

            <a href="{{ route('admin.payments.create') }}"
               class="shrink-0 rounded-sm border border-emerald-500 bg-emerald-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-emerald-700">
                + Buat Tagihan QRIS
                @if ($unpaidOrdersWithoutPayment > 0)
                    ({{ $unpaidOrdersWithoutPayment }})
                @endif
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-emerald-400 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-sm border border-red-400 bg-red-50 p-4 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Kartu Ringkasan KPI --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-xs font-bold uppercase text-gray-900">
                    Total Transaksi
                </div>
                <div class="p-4">
                    <div class="text-3xl font-black text-gray-900">{{ $totalPayments }}</div>
                    <div class="mt-1 text-xs font-semibold text-gray-500">Gateway: {{ $gatewayName }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-xs font-bold uppercase text-gray-900">
                    Menunggu Bayar
                </div>
                <div class="p-4">
                    <div class="text-3xl font-black text-amber-600">{{ $totalPending }}</div>
                    <div class="mt-1 text-xs font-semibold text-gray-500">Status Pending</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-xs font-bold uppercase text-gray-900">
                    Lunas / Settled
                </div>
                <div class="p-4">
                    <div class="text-3xl font-black text-emerald-700">{{ $totalPaid }}</div>
                    <div class="mt-1 text-xs font-semibold text-emerald-700">
                        Rp {{ number_format($totalSettledAmount, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-xs font-bold uppercase text-gray-900">
                    Gagal / Kedaluwarsa
                </div>
                <div class="p-4">
                    <div class="text-3xl font-black text-red-600">{{ $totalFailedOrExpired }}</div>
                    <div class="mt-1 text-xs font-semibold text-gray-500">
                        Mode: {{ $isSandbox ? 'SANDBOX' : 'PRODUCTION' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Tabel --}}
        <div class="space-y-5 rounded-sm border border-emerald-400 bg-white p-6">
            <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $tabs = [
                            'all' => 'Semua',
                            'pending' => 'Pending',
                            'paid' => 'Lunas (Paid)',
                            'failed' => 'Gagal / Batal',
                            'expired' => 'Kedaluwarsa',
                        ];
                    @endphp
                    @foreach ($tabs as $key => $label)
                        <a href="{{ route('admin.payments.index', ['status' => $key, 'search' => $search]) }}"
                           class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === $key ? 'border-emerald-500 bg-emerald-700 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('admin.payments.index') }}" class="flex items-center gap-2">
                    @if ($currentStatus !== 'all')
                        <input type="hidden" name="status" value="{{ $currentStatus }}">
                    @endif
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari ref PAY-, no. order, atau pemesan..."
                           class="w-64 rounded-sm border border-gray-300 px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                    <button type="submit"
                            class="rounded-sm border border-emerald-400 bg-emerald-200 px-3.5 py-1.5 text-xs font-bold text-gray-900 hover:bg-emerald-300">
                        Cari
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('admin.payments.index', ['status' => $currentStatus]) }}"
                           class="text-xs font-semibold text-gray-500 underline hover:text-gray-800">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-emerald-400 text-left text-sm">
                    <thead>
                        <tr class="border-b border-emerald-400 bg-emerald-50/70 text-xs font-bold uppercase text-gray-900">
                            <th class="border-r border-emerald-400 px-3 py-3">Referensi / Order</th>
                            <th class="border-r border-emerald-400 px-3 py-3">Pelanggan &amp; Split</th>
                            <th class="border-r border-emerald-400 px-3 py-3 text-right">Nominal</th>
                            <th class="border-r border-emerald-400 px-3 py-3 text-center">Attempt Terakhir</th>
                            <th class="border-r border-emerald-400 px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-300 text-gray-900">
                        @forelse ($payments as $payment)
                            @php
                                $order = $payment->order;
                                $attempt = $payment->latestAttempt;
                                $attemptExpired = $attempt !== null && ($attempt->status === 'expired' || ($attempt->status === 'pending' && $attempt->isExpired()));
                                $customerName = $order?->customer_snapshot['name'] ?? $order?->user?->name ?? 'Pelanggan Guest';
                                $tenantCount = $order ? $order->tenantOrders->count() : 0;
                            @endphp
                            <tr class="hover:bg-emerald-50/30">
                                <td class="border-r border-emerald-300 px-3 py-3">
                                    <div class="font-mono text-xs font-bold text-gray-900">{{ $payment->payment_reference }}</div>
                                    <div class="mt-0.5 text-xs text-gray-600">
                                        Order: <span class="font-semibold">#{{ $order?->order_number ?? '-' }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400">
                                        {{ $payment->created_at?->setTimezone($tz)->format('d/m/Y H:i') }} WIB
                                    </div>
                                </td>

                                <td class="border-r border-emerald-300 px-3 py-3">
                                    <div class="text-xs font-bold text-gray-900">{{ $customerName }}</div>
                                    <div class="mt-0.5 text-[11px] text-gray-500">
                                        {{ $tenantCount }} toko · Mode {{ $order?->service_mode === 'pickup' ? 'Pre-Order / Pick-up' : 'Delivery' }}
                                    </div>
                                </td>

                                <td class="border-r border-emerald-300 px-3 py-3 text-right font-bold whitespace-nowrap">
                                    Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}
                                </td>

                                <td class="border-r border-emerald-300 px-3 py-3 text-center text-xs">
                                    @if ($attempt === null)
                                        <span class="text-gray-400">Belum ada attempt</span>
                                    @elseif ($attempt->status === 'success')
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">Success</span>
                                    @elseif ($attemptExpired)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">Kedaluwarsa</span>
                                    @else
                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-bold text-blue-800">QRIS Aktif</span>
                                        @if ($attempt->expires_at)
                                            <span class="mt-0.5 block text-[10px] text-gray-500">
                                                s/d {{ $attempt->expires_at->setTimezone($tz)->format('H:i') }} WIB
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                <td class="border-r border-emerald-300 px-3 py-3 text-center">
                                    @if ($payment->status === 'paid')
                                        <span class="rounded-full border border-emerald-300 bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                            Lunas
                                        </span>
                                    @elseif ($payment->status === 'pending')
                                        <span class="rounded-full border border-amber-300 bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">
                                            Pending
                                        </span>
                                    @else
                                        <span class="rounded-full border border-red-300 bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.payments.show', $payment) }}"
                                           class="rounded-sm border border-emerald-400 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-900 hover:bg-emerald-100">
                                            Detail / QRIS
                                        </a>

                                        @if ($payment->status === 'pending' && $order?->isAwaitingPayment())
                                            <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST"
                                                  onsubmit="return confirm('Batalkan pembayaran & pesanan #{{ $order->order_number }}? Stok produk akan dikembalikan.');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="rounded-sm border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700 hover:bg-red-100">
                                                    Batalkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                    Belum ada transaksi pembayaran QRIS yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($payments->hasPages())
                <div class="pt-2">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
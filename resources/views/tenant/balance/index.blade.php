<x-layouts.tenant :tenant="$tenant" title="Saldo & Mutasi Keuangan">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Saldo & Mutasi Keuangan</h2>
            <p class="text-xs text-slate-500 mt-1">Pantau saldo siap tarik, dana tertahan dalam proses pencairan, dan riwayat mutasi toko.</p>
        </div>

        <a href="{{ route('tenant.withdrawals.index', ['tenant' => $tenantSlug]) }}"
           class="inline-flex items-center justify-center px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all">
            Ajukan Penarikan Dana →
        </a>
    </div>

    <!-- Ringkasan Saldo 3 Kolom -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-6 grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
        <div class="p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-slate-400">Saldo Tersedia (Siap Tarik)</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-lg font-bold shrink-0">
                    👛
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-bold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($availableAmount, 0, ',', '.') }}
                </h3>
            </div>
            <p class="text-xs text-emerald-600 font-medium">Dapat ditarik ke rekening terdaftar</p>
        </div>

        <div class="p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-slate-400">Saldo Tertahan (Proses Pencairan)</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-lg font-bold shrink-0">
                    ⏳
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-bold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($heldAmount, 0, ',', '.') }}
                </h3>
            </div>
            <p class="text-xs text-slate-400">Menunggu persetujuan & transfer Admin</p>
        </div>

        <div class="p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-slate-400">Total Dana Dicairkan</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-lg font-bold shrink-0">
                    🏦
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-bold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($totalWithdrawn, 0, ',', '.') }}
                </h3>
            </div>
            <p class="text-xs text-slate-400">Akumulasi pencairan yang disetujui</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Riwayat Mutasi Ledger -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="text-base font-bold text-slate-900 mb-1">Riwayat Mutasi Saldo (Ledger)</h3>
            <p class="text-xs text-slate-400 mb-4">Catatan perubahan saldo tersedia dan saldo tertahan.</p>

            @if($ledgerEntries->isEmpty())
                <div class="py-10 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl">
                    Belum ada catatan mutasi ledger penarikan dana.
                </div>
            @else
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($ledgerEntries as $entry)
                        @php
                            $typeLabel = match ($entry->type) {
                                'sale_credit' => 'Pemasukan Penjualan',
                                'commission_debit' => 'Potongan Komisi',
                                'hold' => 'Penahanan Pengajuan Tarik Dana',
                                'release' => 'Pengembalian Saldo Ditolak',
                                'withdrawal_debit' => 'Pencairan Dana Disetujui',
                                default => ucfirst(str_replace('_', ' ', (string) $entry->type)),
                            };
                            $delta = (int) $entry->available_delta;
                        @endphp
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-800">{{ $typeLabel }}</p>
                                <span class="text-[11px] text-slate-400">
                                    {{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y, H:i') }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold {{ $delta > 0 ? 'text-emerald-600' : ($delta < 0 ? 'text-amber-600' : 'text-slate-600') }}">
                                    {{ $delta > 0 ? '+' : '' }}Rp {{ number_format($delta, 0, ',', '.') }}
                                </span>
                                @if((int) $entry->held_delta !== 0)
                                    <span class="block text-[10px] text-slate-400">
                                        Tertahan: {{ (int) $entry->held_delta > 0 ? '+' : '' }}Rp {{ number_format((int) $entry->held_delta, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Pemasukan dari Pesanan Selesai -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="text-base font-bold text-slate-900 mb-1">Pemasukan Pesanan Selesai</h3>
            <p class="text-xs text-slate-400 mb-4">Pendapatan bersih (net) yang masuk dari transaksi selesai.</p>

            @if($completedOrders->isEmpty())
                <div class="py-10 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl">
                    Belum ada pemasukan dari pesanan selesai.
                </div>
            @else
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($completedOrders as $order)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <a href="{{ route('tenant.orders.show', ['tenant' => $tenantSlug, 'order' => $order->id]) }}"
                                   class="font-semibold text-slate-800 hover:text-emerald-600">
                                    {{ $order->order_number ?? ('#ORD-' . $order->id) }}
                                </a>
                                <span class="block text-[11px] text-slate-400">
                                    {{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y, H:i') }}
                                </span>
                            </div>
                            <span class="font-bold text-emerald-600">
                                +Rp {{ number_format((int) $order->net_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.tenant>
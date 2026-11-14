<x-layouts.tenant title="Detail Pesanan" :tenant="$tenant">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
        $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan';
        $customerPhone = $order->customer_snapshot['phone'] ?? '-';
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('tenant.orders.index', ['tenant' => $tenantSlug]) }}" class="text-xs font-semibold text-emerald-600 hover:underline">
                    ← Kembali ke Daftar Pesanan
                </a>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">
                    Pesanan {{ $order->order_number ?? ('#ORD-' . $order->id) }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Dibuat pada {{ $order->created_at?->format('d M Y, H:i') }}
                </p>
            </div>

            <!-- Tombol Aksi Status -->
            <div class="flex flex-wrap items-center gap-2">
                @if(in_array($order->status, ['pending_payment', 'new_order'], true))
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            Terima & Proses Pesanan
                        </button>
                    </form>
                @endif

                @if($order->status === 'processing')
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_transit">
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                            Kirim Pesanan
                        </button>
                    </form>
                @endif

                @if($order->status === 'in_transit')
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            Tandai Selesai
                        </button>
                    </form>
                @endif

                @if(! in_array($order->status, ['completed', 'cancelled', 'expired'], true))
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}"
                          onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-100 transition">
                            Batalkan
                        </button>
                    </form>
                @endif
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Daftar Produk Dipesan -->
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-slate-900">Item Pesanan</h2>

                <div class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item->name_snapshot }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $item->type_snapshot === 'animal' ? 'Hewan Peliharaan' : 'Produk / Barang' }}
                                    • {{ $item->quantity }} x Rp {{ number_format((int) $item->unit_price_snapshot, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="font-semibold text-slate-900">
                                Rp {{ number_format((int) $item->line_total, 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Rincian Keuangan -->
                <div class="border-t border-slate-200 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Produk</span>
                        <span>Rp {{ number_format((int) $order->subtotal_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Ongkos Kirim</span>
                        <span>Rp {{ number_format((int) $order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Potongan Komisi Platform</span>
                        <span class="text-red-500">- Rp {{ number_format((int) $order->commission_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-emerald-700">
                        <span>Pendapatan Bersih Toko</span>
                        <span>Rp {{ number_format((int) $order->net_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Informasi Pembeli & Pengiriman -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4 h-fit">
                <h2 class="text-base font-bold text-slate-900">Info Pengiriman & Status</h2>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Status Saat Ini</span>
                        <span class="font-semibold text-slate-900 uppercase">{{ str_replace('_', ' ', $order->status) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Nama Pembeli</span>
                        <span class="font-semibold text-slate-900">{{ $customerName }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Kontak</span>
                        <span class="text-slate-700">{{ $customerPhone }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Alamat Pengiriman</span>
                        <p class="text-slate-700 mt-0.5">{{ $order->shipping_address }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
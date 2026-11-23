<x-layouts.tenant title="Detail Pesanan" :tenant="$tenant">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
        $parentOrder = $order->order;
        $payment = $parentOrder?->payment;
        $awaitingQris = $parentOrder !== null && $parentOrder->isAwaitingPayment();
        $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan';
        $customerPhone = $order->customer_snapshot['whatsapp'] ?? $order->customer_snapshot['phone'] ?? '-';
        $shippingAddress = $order->shipping_address ?? ($order->customer_snapshot['address'] ?? null);
        $statusMeta = [
            'pending' => ['Menunggu Pembayaran', 'bg-amber-100 text-amber-700'],
            'pending_payment' => ['Menunggu Pembayaran', 'bg-amber-100 text-amber-700'],
            'scheduled' => ['Terjadwal (Pre-Order)', 'bg-purple-100 text-purple-700'],
            'new_order' => ['Pesanan Baru', 'bg-emerald-100 text-emerald-700'],
            'processing' => ['Sedang Diproses', 'bg-blue-100 text-blue-700'],
            'in_transit' => ['Dikirim (Dalam Perjalanan)', 'bg-indigo-100 text-indigo-700'],
            'completed' => ['Selesai', 'bg-slate-100 text-slate-700'],
            'cancelled' => ['Dibatalkan', 'bg-red-100 text-red-700'],
            'expired' => ['Kedaluwarsa', 'bg-red-100 text-red-700'],
        ][$order->status] ?? [ucfirst(str_replace('_', ' ', $order->status)), 'bg-slate-100 text-slate-700'];
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
                    Dibuat pada {{ $order->created_at?->setTimezone($tz)->format('d M Y, H:i') }} WIB
                </p>
            </div>

            <!-- Tombol Aksi Status -->
            <div class="flex flex-wrap items-center gap-2">
                @if ($awaitingQris)
                    <span class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-semibold text-amber-800">
                        Menunggu pembayaran QRIS pelanggan
                    </span>
                @elseif ($order->status === 'new_order' || $order->status === 'pending_payment')
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            Terima &amp; Proses Pesanan
                        </button>
                    </form>
                @endif

                @if ($order->status === 'processing')
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_transit">
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                            Kirim Pesanan
                        </button>
                    </form>
                @endif

                @if ($order->status === 'in_transit')
                    <form method="POST" action="{{ route('tenant.orders.update-status', ['tenant' => $tenantSlug, 'order' => $order]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            Tandai Selesai
                        </button>
                    </form>
                @endif

                @if (! in_array($order->status, ['completed', 'cancelled', 'expired'], true))
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
                    @foreach ($order->items as $item)
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item->name_snapshot }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $item->type_snapshot === 'animal' ? 'Hewan Peliharaan' : 'Produk / Barang' }}
                                    • {{ $item->quantity }} x Rp {{ number_format((int) $item->unit_price_snapshot, 0, ',', '.') }}
                                </p>
                                @if (! empty($item->note))
                                    <p class="mt-0.5 text-xs italic text-slate-500">Catatan: "{{ $item->note }}"</p>
                                @endif
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
                    @if ((int) $order->tax_amount > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Pajak</span>
                            <span>Rp {{ number_format((int) $order->tax_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if ((int) $order->service_fee_amount > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Biaya Layanan</span>
                            <span>Rp {{ number_format((int) $order->service_fee_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
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

            <!-- Kolom Kanan: Info Pengiriman + Status Pembayaran QRIS -->
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Info Pengiriman &amp; Status</h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-xs text-slate-400 block mb-1">Status Saat Ini</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusMeta[1] }}">
                                {{ $statusMeta[0] }}
                            </span>
                        </div>
                        @if ($order->scheduled_at)
                            <div>
                                <span class="text-xs text-slate-400 block">Jadwal Ambil (Pre-Order)</span>
                                <span class="font-semibold text-purple-700">
                                    {{ \Carbon\CarbonImmutable::parse((string) $order->scheduled_at)->setTimezone($tz)->format('d M Y, H:i') }} WIB
                                </span>
                            </div>
                        @endif
                        <div>
                            <span class="text-xs text-slate-400 block">Nama Pembeli</span>
                            <span class="font-semibold text-slate-900">{{ $customerName }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Kontak / WhatsApp</span>
                            <span class="text-slate-700">{{ $customerPhone }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Alamat Pengiriman</span>
                            <p class="text-slate-700 mt-0.5">
                                @if ($shippingAddress)
                                    {{ $shippingAddress }}
                                @elseif ($parentOrder?->service_mode === 'pickup')
                                    Ambil di Toko (Pick-up)
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Kartu Read-Only Status Pembayaran QRIS --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Status Pembayaran QRIS</h2>

                    @if ($payment)
                        @php
                            $payMeta = match ($payment->status) {
                                'paid' => ['Lunas (Settlement)', 'bg-emerald-100 text-emerald-700'],
                                'pending' => ['Menunggu Pembayaran', 'bg-amber-100 text-amber-700'],
                                'failed' => ['Gagal / Dibatalkan', 'bg-red-100 text-red-700'],
                                'expired' => ['Kedaluwarsa', 'bg-red-100 text-red-700'],
                                default => [ucfirst($payment->status), 'bg-slate-100 text-slate-700'],
                            };
                        @endphp
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-xs text-slate-400 block mb-1">Status Pembayaran</span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $payMeta[1] }}">
                                    {{ $payMeta[0] }}
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block">Referensi Pembayaran</span>
                                <span class="font-mono text-xs font-semibold text-slate-800">{{ $payment->payment_reference }}</span>
                            </div>
                            @if ($parentOrder)
                                <div>
                                    <span class="text-xs text-slate-400 block">Order Induk</span>
                                    <span class="font-semibold text-slate-800">#{{ $parentOrder->order_number }}</span>
                                </div>
                            @endif
                            <div>
                                <span class="text-xs text-slate-400 block">Total Order Induk</span>
                                <span class="font-bold text-slate-900">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block">Waktu Lunas</span>
                                <span class="text-slate-700">
                                    {{ $payment->settled_at ? $payment->settled_at->setTimezone($tz)->format('d M Y, H:i').' WIB' : '-' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-500">
                            Belum ada catatan tagihan QRIS yang terhubung dengan pesanan ini.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.tenant>
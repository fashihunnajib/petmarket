<x-layouts.customer :title="'Status & Pembayaran Pesanan'">
    @php
        $rupiah = static fn (int $n): string => 'Rp'.number_format($n, 0, ',', '.');
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
        $orderBadge = match ($order->status) {
            'paid' => ['active', 'Dibayar'],
            'cancelled' => ['suspended', 'Dibatalkan'],
            'expired' => ['suspended', 'Kedaluwarsa'],
            default => ['pending', 'Menunggu Pembayaran'],
        };
        $customerName = $order->customer_snapshot['name'] ?? null;
        $customerWhatsapp = $order->customer_snapshot['whatsapp'] ?? null;
        $deliveryAddress = $order->shipping_address ?? ($order->customer_snapshot['address'] ?? null);
    @endphp

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-semibold">
            <a href="{{ route('ordering.history') }}" class="text-emerald-700 hover:underline dark:text-emerald-400">
                ← Daftar Pesanan Saya
            </a>
            <a href="{{ route('home') }}" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                Kembali ke Katalog
            </a>
        </div>

        {{-- UC-07: Layar Pembayaran QRIS tampil paling atas setelah checkout --}}
        <livewire:payments::order-payment :order-id="$order->id" />

        {{-- Ringkasan Order Induk --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Nomor Pesanan</p>
                    <p class="mt-0.5 text-lg font-extrabold text-zinc-900 dark:text-zinc-100">{{ $order->order_number }}</p>
                    @if ($order->placed_at)
                        <p class="mt-0.5 text-xs text-zinc-500">
                            Dibuat {{ $order->placed_at->setTimezone($tz)->format('d M Y, H.i') }} WIB
                        </p>
                    @endif
                </div>
                <x-status-badge :status="$orderBadge[0]">{{ $orderBadge[1] }}</x-status-badge>
            </div>

            <div class="mt-4 space-y-1.5 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                <p data-test="service-mode">
                    <span class="font-medium text-zinc-500">Mode layanan:</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $order->service_mode === 'pickup' ? 'Ambil di Toko / Pre-Order' : 'Kirim ke Alamat' }}
                    </span>
                </p>
                @if ($customerName)
                    <p>
                        <span class="font-medium text-zinc-500">Pemesan:</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $customerName }}</span>
                        @if ($customerWhatsapp)
                            <span class="text-zinc-500"> · {{ $customerWhatsapp }}</span>
                        @endif
                    </p>
                @endif
                @if ($deliveryAddress)
                    <p>
                        <span class="font-medium text-zinc-500">Alamat pengiriman:</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ $deliveryAddress }}</span>
                    </p>
                @endif
            </div>
        </div>

        {{-- Rincian Sub-Pesanan per Toko --}}
        @foreach ($order->tenantOrders as $tenantOrder)
            @php
                $tenantBadge = match ($tenantOrder->status) {
                    'new_order', 'completed' => ['active', $tenantOrder->status === 'completed' ? 'Selesai' : 'Pesanan Baru'],
                    'processing' => ['pending', 'Diproses'],
                    'in_transit' => ['pending', 'Dikirim'],
                    'scheduled' => ['pending', 'Terjadwal'],
                    'cancelled', 'expired' => ['suspended', $tenantOrder->status === 'cancelled' ? 'Dibatalkan' : 'Kedaluwarsa'],
                    default => ['pending', 'Menunggu Bayar'],
                };
            @endphp
            <div wire:key="to-{{ $tenantOrder->id }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $tenantOrder->tenant?->name ?? 'Toko #'.$tenantOrder->tenant_id }}
                        </h2>
                        <p class="text-xs text-zinc-500">{{ $tenantOrder->order_number }}</p>
                    </div>

                    @if ($tenantOrder->status === 'scheduled' && $tenantOrder->scheduled_at)
                        <x-status-badge status="pending">
                            Terjadwal · ambil {{ \Carbon\CarbonImmutable::parse((string) $tenantOrder->scheduled_at)->setTimezone($tz)->format('d M, H.i') }} WIB
                        </x-status-badge>
                    @else
                        <x-status-badge :status="$tenantBadge[0]">{{ $tenantBadge[1] }}</x-status-badge>
                    @endif
                </div>

                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($tenantOrder->items as $item)
                        <li class="flex items-start justify-between gap-3 py-2.5 text-sm">
                            <div class="min-w-0">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $item->quantity }}× {{ $item->name_snapshot }}
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $rupiah((int) $item->unit_price_snapshot) }} / item
                                </p>
                                @if (! empty($item->note))
                                    <p class="mt-0.5 text-xs italic text-zinc-500">Catatan: "{{ $item->note }}"</p>
                                @endif
                            </div>
                            <p class="shrink-0 font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $rupiah((int) $item->line_total) }}
                            </p>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-3 border-t border-zinc-100 pt-2.5 text-right text-xs font-semibold text-zinc-500 dark:border-zinc-800">
                    Subtotal toko: <span class="text-zinc-900 dark:text-zinc-100">{{ $rupiah((int) $tenantOrder->subtotal_amount) }}</span>
                </p>
            </div>
        @endforeach

        {{-- Ringkasan Total Biaya --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-zinc-500">Rincian Biaya</h3>
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Subtotal</dt>
                    <dd class="font-medium">{{ $rupiah((int) $order->subtotal_amount) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Pajak</dt>
                    <dd class="font-medium">{{ $rupiah((int) $order->tax_amount) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Biaya layanan</dt>
                    <dd class="font-medium">{{ $rupiah((int) $order->service_fee_amount) }}</dd>
                </div>
                @if ((int) $order->shipping_cost > 0)
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">Ongkos kirim</dt>
                        <dd class="font-medium">{{ $rupiah((int) $order->shipping_cost) }}</dd>
                    </div>
                @endif
                <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2.5 text-base font-extrabold dark:border-zinc-800">
                    <dt>Total Pembayaran</dt>
                    <dd class="text-emerald-600 dark:text-emerald-400">{{ $rupiah((int) $order->grand_total_amount) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-layouts.customer>
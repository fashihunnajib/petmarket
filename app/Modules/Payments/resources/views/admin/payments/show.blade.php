<x-layouts.admin title="Detail Pembayaran QRIS">
    @php
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
        $order = $payment->order;
        $customerName = $order?->customer_snapshot['name'] ?? $order?->user?->name ?? 'Pelanggan Guest';
        $customerWhatsapp = $order?->customer_snapshot['whatsapp'] ?? '-';
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <h2 class="whitespace-nowrap text-xl font-bold text-gray-900">
                    Detail Pembayaran · {{ $payment->payment_reference }}
                </h2>
                <div class="h-[2px] w-full bg-emerald-400"></div>
            </div>

            <a href="{{ route('admin.payments.index') }}"
               class="shrink-0 rounded-sm border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100">
                ← Kembali ke Daftar
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

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Kolom Kiri: Info Utama + Split per Tenant --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Ringkasan Payment & Order --}}
                <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 pb-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-gray-500">Nomor Pesanan Induk</p>
                            <p class="text-lg font-black text-gray-900">#{{ $order?->order_number ?? '-' }}</p>
                            <p class="mt-0.5 font-mono text-xs text-gray-500">
                                Idempotency Key: {{ $payment->idempotency_key }}
                            </p>
                        </div>

                        <div>
                            @if ($payment->status === 'paid')
                                <span class="rounded-full border border-emerald-300 bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                    LUNAS (SETTLEMENT)
                                </span>
                            @elseif ($payment->status === 'pending')
                                <span class="rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                    MENUNGGU PEMBAYARAN
                                </span>
                            @else
                                <span class="rounded-full border border-red-300 bg-red-100 px-3 py-1 text-xs font-bold text-red-800">
                                    {{ strtoupper($payment->status) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-gray-500">Total Nominal (Terkunci dari Order)</dt>
                            <dd class="text-xl font-black text-emerald-700">
                                Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Waktu Lunas (Settled At)</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $payment->settled_at ? $payment->settled_at->setTimezone($tz)->format('d M Y, H:i:s').' WIB' : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Pelanggan</dt>
                            <dd class="font-semibold text-gray-900">{{ $customerName }}</dd>
                            <dd class="text-xs text-gray-500">WhatsApp: {{ $customerWhatsapp }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Gateway &amp; Mode</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $gatewayName }} ({{ $isSandbox ? 'Sandbox' : 'Production' }})
                            </dd>
                            <dd class="text-xs text-gray-500">
                                Status Order: <span class="font-semibold">{{ $order?->status ?? '-' }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Rincian Split per Tenant --}}
                <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900">
                        Rincian Split Pembayaran per Tenant
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-emerald-400 text-xs">
                            <thead>
                                <tr class="border-b border-emerald-400 bg-emerald-50/70 font-bold text-gray-900">
                                    <th class="border-r border-emerald-400 px-3 py-2 text-left">Toko / Sub-Order</th>
                                    <th class="border-r border-emerald-400 px-3 py-2 text-center">Status</th>
                                    <th class="border-r border-emerald-400 px-3 py-2 text-right">Gross</th>
                                    <th class="border-r border-emerald-400 px-3 py-2 text-right">Komisi</th>
                                    <th class="px-3 py-2 text-right">Net Tenant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-300">
                                @forelse ($tenantOrders as $to)
                                    @php
                                        $gross = (int) ($to->subtotal_amount + $to->tax_amount + $to->service_fee_amount + $to->shipping_cost);
                                    @endphp
                                    <tr>
                                        <td class="border-r border-emerald-300 px-3 py-2">
                                            <div class="font-bold text-gray-900">{{ $to->tenant?->name ?? 'Toko #'.$to->tenant_id }}</div>
                                            <div class="font-mono text-[11px] text-gray-500">{{ $to->order_number }}</div>
                                        </td>
                                        <td class="border-r border-emerald-300 px-3 py-2 text-center font-semibold">
                                            {{ $to->status }}
                                        </td>
                                        <td class="border-r border-emerald-300 px-3 py-2 text-right font-semibold">
                                            Rp {{ number_format($gross, 0, ',', '.') }}
                                        </td>
                                        <td class="border-r border-emerald-300 px-3 py-2 text-right text-red-600">
                                            - Rp {{ number_format((int) $to->commission_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold text-emerald-700">
                                            Rp {{ number_format((int) $to->net_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-gray-500">Tidak ada rincian sub-pesanan tenant.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Riwayat Payment Attempts --}}
                <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900">
                        Riwayat Percobaan QRIS ({{ $attempts->count() }} Attempt)
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-emerald-400 text-xs">
                            <thead>
                                <tr class="border-b border-emerald-400 bg-emerald-50/70 font-bold text-gray-900">
                                    <th class="border-r border-emerald-400 px-3 py-2 text-left">Provider Reference</th>
                                    <th class="border-r border-emerald-400 px-3 py-2 text-center">Status</th>
                                    <th class="border-r border-emerald-400 px-3 py-2 text-left">Kedaluwarsa</th>
                                    <th class="px-3 py-2 text-left">Dibuat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-300">
                                @forelse ($attempts as $attempt)
                                    <tr>
                                        <td class="border-r border-emerald-300 px-3 py-2 font-mono font-semibold text-gray-800">
                                            {{ $attempt->provider_reference }}
                                        </td>
                                        <td class="border-r border-emerald-300 px-3 py-2 text-center font-bold">
                                            {{ $attempt->status }}
                                        </td>
                                        <td class="border-r border-emerald-300 px-3 py-2">
                                            {{ $attempt->expires_at ? $attempt->expires_at->setTimezone($tz)->format('d/m/Y H:i:s').' WIB' : '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-500">
                                            {{ $attempt->created_at?->setTimezone($tz)->format('d/m/Y H:i:s') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-gray-500">Belum ada percobaan QRIS.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Log Append-Only Payment Events --}}
                <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900">
                        Payment Events (Append-Only Audit · {{ $events->count() }})
                    </h3>

                    @forelse ($events as $event)
                        <div class="rounded-sm border border-gray-200 bg-gray-50 p-3 text-xs space-y-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono font-bold text-gray-900">{{ $event->provider_event_id }}</span>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                    {{ strtoupper($event->result) }}
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500">
                                Diterima: {{ $event->received_at?->setTimezone($tz)->format('d/m/Y H:i:s') }} WIB
                            </p>
                            <pre class="overflow-x-auto rounded bg-white p-2 font-mono text-[11px] text-gray-700">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">Belum ada event verifikasi pembayaran dari gateway.</p>
                    @endforelse
                </div>
            </div>

            {{-- Kolom Kanan: Preview QRIS & Panel Aksi CRUD --}}
            <div class="space-y-4">
                <div class="rounded-sm border border-emerald-400 bg-white p-5 space-y-4">
                    <h3 class="border-b border-gray-200 pb-2 text-sm font-bold uppercase tracking-wider text-gray-900">
                        Kode QRIS &amp; Aksi
                    </h3>

                    @if ($qrSvg && $latestAttempt)
                        <div class="rounded-sm border border-gray-300 bg-gray-50 p-3 text-center">
                            <div class="mb-2 flex items-center justify-between text-xs font-bold">
                                <span>QRIS</span>
                                <span class="text-gray-500">NMID {{ $maskedNmid }}</span>
                            </div>
                            <div class="mx-auto w-fit rounded bg-white p-2 [&>svg]:size-48">
                                {!! $qrSvg !!}
                            </div>
                            @if ($latestAttempt->expires_at)
                                <p class="mt-2 text-xs text-gray-600">
                                    Berlaku s/d <strong>{{ $latestAttempt->expires_at->setTimezone($tz)->format('H:i:s') }} WIB</strong>
                                </p>
                            @endif
                        </div>
                    @elseif ($payment->isPaid())
                        <div class="rounded-sm border border-emerald-300 bg-emerald-50 p-3 text-center text-xs font-semibold text-emerald-800">
                            Tagihan telah lunas dan diverifikasi.
                        </div>
                    @elseif ($isExpired)
                        <div class="rounded-sm border border-amber-300 bg-amber-50 p-3 text-center text-xs font-semibold text-amber-900">
                            Attempt QRIS terakhir kedaluwarsa. Gunakan tombol di bawah untuk membuat QRIS baru.
                        </div>
                    @else
                        <div class="rounded-sm border border-gray-200 bg-gray-50 p-3 text-center text-xs text-gray-600">
                            Tidak ada QRIS aktif pada pembayaran ini.
                        </div>
                    @endif

                    {{-- Tombol Aksi Update / Sandbox / Cancel --}}
                    <div class="space-y-2 pt-2">
                        @if (! $payment->isPaid() && $order?->isAwaitingPayment())
                            @if (! $qrSvg)
                                <form action="{{ route('admin.payments.regenerate', $payment) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="w-full rounded-sm border border-emerald-500 bg-emerald-600 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-700">
                                        Buat QRIS Baru
                                    </button>
                                </form>
                            @endif

                            @if ($isSandbox && $qrSvg)
                                <form action="{{ route('admin.payments.simulate-pay', $payment) }}" method="POST"
                                      onsubmit="return confirm('Simulasikan pembayaran sukses (sandbox) untuk {{ $payment->payment_reference }}?');">
                                    @csrf
                                    <button type="submit"
                                            class="w-full rounded-sm border border-emerald-500 bg-emerald-200 px-4 py-2 text-xs font-bold uppercase text-gray-900 hover:bg-emerald-300">
                                        Simulasi Bayar (Sandbox)
                                    </button>
                                </form>

                                <form action="{{ route('admin.payments.expire', $payment) }}" method="POST"
                                      onsubmit="return confirm('Tandai QRIS aktif menjadi kedaluwarsa untuk uji alur regenerasi (4a)?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-full rounded-sm border border-amber-400 bg-amber-50 px-4 py-2 text-xs font-bold uppercase text-amber-800 hover:bg-amber-100">
                                        Simulasi Kedaluwarsa (Sandbox)
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST"
                                  onsubmit="return confirm('Batalkan pembayaran & pesanan ini? Stok produk akan dikembalikan secara atomik.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full rounded-sm border border-red-400 bg-red-50 px-4 py-2 text-xs font-bold uppercase text-red-700 hover:bg-red-100">
                                    Batalkan Pembayaran &amp; Pesanan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
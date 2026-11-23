<x-layouts.customer :title="'Pesanan Saya'">
    @php
        $rupiah = static fn (int $n): string => 'Rp'.number_format($n, 0, ',', '.');
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-zinc-900 dark:text-zinc-100">Pesanan &amp; Pembayaran Saya</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    Pantau status pesanan, lanjutkan pembayaran QRIS yang tertunda, atau buat QRIS baru bila kedaluwarsa.
                </p>
            </div>
            <a href="{{ route('home') }}"
               class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700">
                + Belanja Lagi
            </a>
        </div>

        @if ($orders->isEmpty())
            <x-empty-state
                title="Belum ada pesanan"
                description="Pesanan yang Anda buat melalui checkout akan muncul di sini beserta status pembayaran QRIS-nya."
            >
                <x-slot:action>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <a href="{{ route('home') }}"
                           class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Jelajahi Katalog
                        </a>
                        @guest
                            <a href="{{ route('login', ['redirect' => route('ordering.history', [], false)]) }}"
                               class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                Masuk untuk Lihat Riwayat Akun
                            </a>
                        @endguest
                    </div>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="space-y-4">
                @foreach ($orders as $order)
                    @php
                        $payment = $order->payment;
                        $attempt = $payment?->latestAttempt;
                        $isExpiredAttempt = $attempt !== null && ($attempt->status === 'expired' || ($attempt->status === 'pending' && $attempt->isExpired()));
                        $badge = match ($order->status) {
                            'paid' => ['active', 'Lunas (Paid)'],
                            'cancelled' => ['suspended', 'Dibatalkan'],
                            'expired' => ['suspended', 'Kedaluwarsa'],
                            default => $isExpiredAttempt
                                ? ['suspended', 'QRIS Kedaluwarsa']
                                : ['pending', 'Menunggu Pembayaran'],
                        };
                        $tenantNames = $order->tenantOrders
                            ->map(static fn ($to): string => $to->tenant?->name ?? 'Toko #'.$to->tenant_id)
                            ->unique()
                            ->implode(', ');
                    @endphp

                    <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">
                                        #{{ $order->order_number }}
                                    </h2>
                                    <x-status-badge :status="$badge[0]">{{ $badge[1] }}</x-status-badge>
                                    @if ($trackedOrderId === $order->id)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            Sesi aktif
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $order->placed_at?->setTimezone($tz)->format('d M Y, H.i') ?? $order->created_at?->setTimezone($tz)->format('d M Y, H.i') }} WIB
                                    @if ($tenantNames !== '')
                                        · {{ $tenantNames }}
                                    @endif
                                </p>
                                @if ($payment)
                                    <p class="mt-1 font-mono text-xs text-zinc-500">
                                        Ref QRIS: {{ $payment->payment_reference }}
                                    </p>
                                @endif
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-zinc-500">Total</p>
                                <p class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">
                                    {{ $rupiah((int) $order->grand_total_amount) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <span class="text-xs text-zinc-500">
                                Mode: {{ $order->service_mode === 'pickup' ? 'Ambil di Toko / Pre-Order' : 'Kirim ke Alamat' }}
                            </span>

                            <a href="{{ route('ordering.status', ['order' => $order->public_id]) }}"
                               class="inline-flex items-center rounded-xl px-4 py-2 text-xs font-bold transition {{ $order->isAwaitingPayment() ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'border border-zinc-300 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800' }}">
                                @if ($order->isAwaitingPayment())
                                    {{ $isExpiredAttempt ? 'Buat QRIS Baru →' : 'Lanjut Bayar QRIS →' }}
                                @else
                                    Lihat Detail Pesanan →
                                @endif
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.customer>
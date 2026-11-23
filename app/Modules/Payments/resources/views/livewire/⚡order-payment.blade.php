<?php

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Modules\Ordering\Exceptions\CheckoutException;
use App\Modules\Ordering\Services\ResolveTrackedOrder;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentException;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Payments\Support\QrisImage;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $orderId = 0;

    public string $paymentError = '';

    public string $initialStatus = '';

    public function mount(int $orderId = 0): void
    {
        $this->orderId = $orderId;
        $order = $this->order;
        $this->initialStatus = $order?->status ?? '';

        // Hanya bangkitkan otomatis saat pertama kali dibuka (belum ada attempt sebelumnya).
        // Jika attempt terakhir sudah kedaluwarsa, tampilkan pesan kedaluwarsa + tombol "Buat QRIS baru" (UC-07 alur 4a).
        if ($order !== null && $order->isAwaitingPayment() && $order->payment?->latestAttempt === null) {
            $this->tryInitiate($order);
        }
    }

    #[Computed]
    public function order(): ?Order
    {
        $order = app(ResolveTrackedOrder::class)->resolveForRequest(
            request(),
            $this->orderId > 0 ? $this->orderId : null,
        );

        return $order?->load('payment.latestAttempt');
    }

    #[Computed]
    public function sandbox(): bool
    {
        return app(PaymentGateway::class)->isSandbox();
    }

    private function attempt(): ?PaymentAttempt
    {
        return $this->order?->payment?->latestAttempt;
    }

    public function expired(): bool
    {
        $attempt = $this->attempt();

        return $attempt !== null && ($attempt->status === 'expired' || ($attempt->status === 'pending' && $attempt->isExpired()));
    }

    #[Computed]
    public function qrSvg(): ?string
    {
        $attempt = $this->attempt();
        if ($attempt === null || $attempt->isExpired() || $attempt->status !== 'pending' || ! $attempt->qris_payload) {
            return null;
        }

        return QrisImage::svg((string) $attempt->qris_payload);
    }

    #[Computed]
    public function maskedNmid(): string
    {
        $nmid = (string) config('services.qris.merchant_nmid', '');

        return $nmid !== '' ? '•••'.substr($nmid, -4) : '-';
    }

    public function retryInitiate(): void
    {
        $order = $this->order;
        if ($order !== null) {
            $this->tryInitiate($order);
        }
    }

    public function regenerate(): void
    {
        $order = $this->order;
        if ($order !== null) {
            $this->tryInitiate($order);
        }
    }

    public function cancel(): void
    {
        $order = $this->order;
        if ($order === null || ! $order->isAwaitingPayment()) {
            return;
        }

        try {
            app(PaymentService::class)->cancelUnpaid($order);
            $this->paymentError = '';
        } catch (CheckoutException|PaymentException $e) {
            $this->paymentError = $e->getMessage();
        }

        unset($this->order, $this->qrSvg);
    }

    public function simulatePay(): void
    {
        $payment = $this->order?->payment;
        if ($payment === null) {
            return;
        }

        try {
            app(PaymentService::class)->confirmSandbox($payment);
            $this->paymentError = '';
        } catch (PaymentException $e) {
            $this->paymentError = $e->getMessage();
        }

        unset($this->order, $this->qrSvg);
    }

    private function tryInitiate(Order $order): void
    {
        $this->paymentError = '';

        try {
            app(PaymentService::class)->initiate($order);
        } catch (PaymentException $e) {
            $this->paymentError = $e->getMessage();
        }

        unset($this->order, $this->qrSvg);
    }
};
?>

@php
    $order = $this->order;
    $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
@endphp

<section class="overflow-hidden rounded-2xl border-2 border-zinc-900 bg-white shadow-sm dark:border-zinc-100 dark:bg-zinc-900"
    aria-label="Pembayaran" data-test="payment"
    @if ($order?->isAwaitingPayment()) wire:poll.3s @endif>
    @if (! $order)
        <div class="p-5">
            <x-empty-state title="Pesanan tidak ditemukan" />
        </div>
    @else
        @php
            $payment = $order->payment;
            $attempt = $payment?->latestAttempt;
            $isExpired = $this->expired();
            $amount = (int) ($payment?->amount ?? $order->grand_total_amount);
        @endphp

        <div class="flex items-baseline justify-between border-b-2 border-zinc-900 px-5 py-4 dark:border-zinc-100">
            <h2 class="text-xl font-extrabold text-zinc-900 dark:text-zinc-100">Pembayaran QRIS</h2>
            <span class="text-sm font-semibold text-zinc-500">#{{ $order->order_number }}</span>
        </div>

        <div class="space-y-4 p-5">
            @if ($paymentError !== '')
                <div class="rounded-xl border-l-4 border-red-600 bg-red-50 p-3.5 text-sm font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-200"
                    role="alert" data-test="payment-error">
                    <p>{{ $paymentError }}</p>
                    @if (! $this->qrSvg)
                        <button type="button" wire:click="retryInitiate" wire:loading.attr="disabled" data-test="retry-payment"
                            class="mt-2.5 rounded-lg bg-red-700 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-red-800 disabled:opacity-60">
                            Coba lagi
                        </button>
                    @endif
                </div>
            @endif

            @if (in_array($initialStatus, ['awaiting_payment', 'pending_payment'], true) && ! $order->isAwaitingPayment())
                {{-- Muat ulang sekali agar badge status & ringkasan pesanan di luar komponen ikut terbarui --}}
                <span x-init="setTimeout(() => window.location.reload(), 1500)"></span>
            @endif

            @if ($order->status === 'paid')
                {{-- Mockup Gambar 10.2: State Pembayaran Terverifikasi --}}
                <div class="space-y-4">
                    <div class="rounded-xl bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200" role="status">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex size-6 items-center justify-center rounded-md bg-emerald-600 text-xs font-bold text-white">✓</span>
                            <span>Pembayaran berhasil. Pesanan diteruskan ke toko!</span>
                        </div>
                    </div>

                    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-800">
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <dt class="text-zinc-500">Nomor pesanan</dt>
                            <dd class="font-bold text-zinc-900 dark:text-zinc-100">#{{ $order->order_number }}</dd>
                        </div>
                        @if ($payment)
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <dt class="text-zinc-500">Referensi pembayaran</dt>
                                <dd class="font-mono text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $payment->payment_reference }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <dt class="text-zinc-500">Status</dt>
                            <dd>
                                <span class="rounded bg-emerald-600 px-2 py-0.5 text-[11px] font-extrabold tracking-wider text-white">
                                    SETTLEMENT
                                </span>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <dt class="text-zinc-500">Dibayar</dt>
                            <dd class="font-bold text-zinc-900 dark:text-zinc-100">
                                Rp{{ number_format($amount, 0, ',', '.') }}
                                @if ($payment?->settled_at)
                                    · {{ $payment->settled_at->setTimezone($tz)->format('H.i') }} WIB
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @elseif ($order->status === 'cancelled')
                <div class="space-y-3">
                    <div class="rounded-xl bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        role="status" data-test="payment-cancelled">
                        Pesanan dibatalkan. Stok produk telah dikembalikan.
                    </div>
                    <a href="{{ route('home') }}"
                       class="flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white hover:bg-emerald-700">
                        Kembali ke Katalog
                    </a>
                </div>
            @else
                <div class="text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total pembayaran</p>
                    <p class="mt-1 text-4xl font-extrabold text-zinc-900 dark:text-zinc-100" data-test="payment-amount">
                        Rp{{ number_format($amount, 0, ',', '.') }}
                    </p>
                </div>

                @if ($this->qrSvg && $attempt)
                    @php
                        $total = max(1, (int) config('services.qris.expiry_seconds', 900));
                        $leftSeconds = $attempt->expires_at !== null
                            ? max(0, (int) now()->diffInSeconds($attempt->expires_at, false))
                            : 0;
                    @endphp
                    <div class="rounded-xl border-2 border-zinc-900 p-4 dark:border-zinc-100"
                        x-data="{
                            left: {{ $leftSeconds }},
                            total: {{ $total }},
                            tick() {
                                if (this.left > 0) {
                                    this.left--;
                                    if (this.left === 0) {
                                        $wire.$refresh();
                                    }
                                }
                            },
                            get label() {
                                return String(Math.floor(this.left / 60)).padStart(2, '0') + ':' + String(this.left % 60).padStart(2, '0');
                            }
                        }"
                        x-init="setInterval(() => tick(), 1000)">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="font-extrabold tracking-widest text-zinc-900 dark:text-zinc-100">QRIS</span>
                            <span class="text-xs font-semibold uppercase text-zinc-500">
                                {{ config('app.name', 'PetMarket') }} · NMID {{ $this->maskedNmid }}
                            </span>
                        </div>

                        <div class="mx-auto my-3 w-fit rounded-lg bg-white p-2 [&>svg]:size-56"
                            data-test="qris-image" role="img"
                            aria-label="Kode QRIS nominal Rp{{ number_format($amount, 0, ',', '.') }}">
                            {!! $this->qrSvg !!}
                        </div>

                        <div class="flex items-baseline justify-between border-t border-zinc-200 pt-2.5 text-sm dark:border-zinc-700">
                            <span class="text-zinc-600 dark:text-zinc-400">Berlaku hingga</span>
                            <span class="font-mono text-xl font-extrabold text-red-600" x-text="label" data-test="countdown">
                                {{ $attempt->expires_at?->setTimezone($tz)->format('H.i') }}
                            </span>
                        </div>

                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-1.5 bg-red-600 transition-all duration-1000"
                                :style="`width: ${Math.min(100, (left / total) * 100)}%`"></div>
                        </div>

                        @if ($payment)
                            <details class="mt-3 text-xs text-zinc-500">
                                <summary class="cursor-pointer font-medium hover:text-zinc-800 dark:hover:text-zinc-200">
                                    Referensi {{ $payment->payment_reference }}
                                </summary>
                                <p class="mt-1.5 break-all rounded-lg bg-zinc-50 p-2 font-mono text-[11px] dark:bg-zinc-950">
                                    {{ $attempt->qris_payload }}
                                </p>
                            </details>
                        @endif
                    </div>

                    <p class="flex items-center justify-center gap-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200" role="status">
                        <span class="size-2.5 animate-pulse rounded-full bg-red-600"></span>
                        Menunggu pembayaran…
                    </p>

                    <ol class="list-decimal space-y-1 ps-5 text-sm text-zinc-600 dark:text-zinc-400">
                        <li>Buka aplikasi pembayaran (GoPay, OVO, DANA, BCA, dll.).</li>
                        <li>Pindai kode QRIS di atas — nominal sudah terkunci.</li>
                        <li>Status terverifikasi otomatis, tanpa unggah bukti.</li>
                    </ol>
                @elseif ($isExpired)
                    <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4 text-center text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100"
                        data-test="qris-expired">
                        <p class="font-bold">QRIS kedaluwarsa sebelum dibayar.</p>
                        <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">
                            Transaksi QRIS dibatalkan; pesanan tetap menunggu pembayaran.
                        </p>
                        <button type="button" wire:click="regenerate" wire:loading.attr="disabled" data-test="regenerate"
                            class="mt-3 min-h-11 w-full rounded-xl bg-red-600 px-4 font-bold text-white hover:bg-red-700 disabled:opacity-60">
                            Buat QRIS baru
                        </button>
                    </div>
                @elseif ($paymentError === '')
                    <button type="button" wire:click="retryInitiate" wire:loading.attr="disabled" data-test="retry-payment"
                        class="min-h-11 w-full rounded-xl bg-emerald-600 font-bold text-white hover:bg-emerald-700 disabled:opacity-60">
                        {{ $payment ? 'Coba lagi' : 'Bayar dengan QRIS' }}
                    </button>
                @endif

                <div class="space-y-2.5 pt-1">
                    @if ($this->sandbox && $this->qrSvg)
                        <button type="button" wire:click="simulatePay" wire:loading.attr="disabled" data-test="simulate-pay"
                            class="min-h-11 w-full rounded-xl border-2 border-dashed border-emerald-600 bg-emerald-50/60 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-200">
                            Simulasi Bayar (sandbox)
                        </button>
                    @endif

                    <button type="button" wire:click="cancel" wire:confirm="Batalkan pesanan ini? Stok produk akan dikembalikan."
                        wire:loading.attr="disabled" data-test="cancel-order"
                        class="min-h-11 w-full rounded-xl border-2 border-zinc-900 text-sm font-bold text-zinc-900 transition hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800">
                        Batalkan pesanan
                    </button>
                </div>
            @endif
        </div>
    @endif
</section>
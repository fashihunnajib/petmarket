<?php

use App\Models\Order;
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

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
        $order = $this->order;
        $this->initialStatus = $order?->status ?? '';

        // Hanya bangkitkan otomatis saat pertama kali dibuka (belum ada attempt sebelumnya).
        // Jika attempt terakhir sudah kedaluwarsa, biarkan layar menampilkan pesan kedaluwarsa
        // dan tombol "Buat QRIS baru" (UC-07 alur 4a).
        if ($order !== null && $order->isAwaitingPayment() && $order->payment?->latestAttempt === null) {
            $this->tryInitiate($order);
        }
    }

    #[Computed]
    public function order(): ?Order
    {
        if (! auth()->check()) {
            return null;
        }

        return Order::query()
            ->whereKey($this->orderId)
            ->where('user_id', auth()->id())
            ->with('payment.latestAttempt')
            ->first();
    }

    #[Computed]
    public function sandbox(): bool
    {
        return app(PaymentGateway::class)->isSandbox();
    }

    #[Computed]
    public function qrSvg(): ?string
    {
        $attempt = $this->order?->payment?->latestAttempt;
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

        app(PaymentService::class)->cancelUnpaid($order);
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

@php($order = $this->order)
<section class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800" aria-label="Pembayaran" data-test="payment"
    @if ($order?->isAwaitingPayment()) wire:poll.3s @endif>
    @if (! $order)
        <x-empty-state title="Pesanan tidak ditemukan" />
    @else
        @php($payment = $order->payment)
        @php($attempt = $payment?->latestAttempt)
        @php($isExpired = $attempt !== null && ($attempt->status === 'expired' || $attempt->isExpired()))

        <div class="flex items-baseline justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
            <h2 class="text-lg font-bold">Pembayaran QRIS</h2>
            <span class="text-sm font-medium text-zinc-500">#{{ $order->order_number }}</span>
        </div>

        <div class="mt-4 space-y-4">
            @if ($paymentError !== '')
                <div class="rounded-xl border border-red-300 bg-red-50 p-3 text-sm font-medium text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert" data-test="payment-error">
                    <p>{{ $paymentError }}</p>
                    @if (! $this->qrSvg)
                        <button type="button" wire:click="retryInitiate" wire:loading.attr="disabled"
                            class="mt-2 rounded-lg bg-red-700 px-3 py-1.5 text-xs font-semibold text-white">
                            Coba lagi
                        </button>
                    @endif
                </div>
            @endif

            @if ($order->status === 'paid')
                <div class="rounded-xl bg-green-100 px-4 py-3 text-sm font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300" role="status">
                    Pembayaran berhasil. Pesanan diteruskan ke toko!
                </div>
            @elseif ($order->status === 'cancelled')
                <div class="rounded-xl bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" role="status" data-test="payment-cancelled">
                    Pesanan dibatalkan. Stok produk telah dikembalikan.
                </div>
            @else
                <div class="text-center">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Menunggu pembayaran</p>
                    <p class="mt-1 text-3xl font-extrabold" data-test="payment-amount">Rp{{ number_format($payment->amount ?? $order->grand_total_amount, 0, ',', '.') }}</p>
                </div>

                @if ($this->qrSvg && $attempt)
                    @php($total = max(1, (int) config('services.qris.expiry_seconds', 900)))
                    <div class="rounded-xl border border-zinc-300 p-4 dark:border-zinc-700"
                        x-data="{ left: {{ max(0, (int) now()->diffInSeconds($attempt->expires_at, false)) }}, total: {{ $total }},
                                  tick() { if (this.left > 0) { this.left--; if (this.left === 0) { $wire.$refresh() } } },
                                  get label() { return String(Math.floor(this.left / 60)).padStart(2, '0') + ':' + String(this.left % 60).padStart(2, '0') } }"
                        x-init="setInterval(() => tick(), 1000)">
                        <div class="flex items-baseline justify-between">
                            <span class="font-extrabold tracking-widest">QRIS</span>
                            <span class="text-xs font-semibold uppercase text-zinc-500">{{ config('app.name', 'PetMarket') }} · NMID {{ $this->maskedNmid }}</span>
                        </div>
                        <div class="mx-auto my-3 w-fit rounded-lg bg-white p-2 [&>svg]:size-56" data-test="qris-image" role="img" aria-label="Kode QRIS">
                            {!! $this->qrSvg !!}
                        </div>
                        <div class="flex items-baseline justify-between border-t border-zinc-200 pt-2 text-sm dark:border-zinc-700">
                            <span>Berlaku selama</span>
                            <span class="font-mono font-bold" x-text="label"></span>
                        </div>
                    </div>
                @elseif ($isExpired)
                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-center text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        <p class="font-semibold">QRIS kedaluwarsa sebelum dibayar.</p>
                        <button type="button" wire:click="regenerate" wire:loading.attr="disabled"
                            class="mt-3 min-h-10 rounded-xl bg-zinc-900 px-4 font-semibold text-white dark:bg-white dark:text-zinc-900">
                            Buat QRIS baru
                        </button>
                    </div>
                @endif

                <div class="space-y-2 pt-1">
                    @if ($this->sandbox && $this->qrSvg)
                        <button type="button" wire:click="simulatePay" wire:loading.attr="disabled"
                            class="min-h-11 w-full rounded-xl bg-zinc-900 font-semibold text-white disabled:opacity-50 dark:bg-white dark:text-zinc-900">
                            Simulasi Bayar (sandbox)
                        </button>
                    @endif

                    <button type="button" wire:click="cancel" wire:loading.attr="disabled"
                        class="min-h-10 w-full rounded-xl border border-zinc-300 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-300">
                        Batalkan pesanan
                    </button>
                </div>
            @endif
        </div>
    @endif
</section>
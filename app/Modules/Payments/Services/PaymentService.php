<?php

namespace App\Modules\Payments\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\TenantOrder;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Ordering\Services\CancelUnpaidOrder;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Data\PaymentChargeRequest;
use App\Modules\Payments\Data\QrisCharge;
use App\Modules\Payments\Exceptions\GatewayUnavailableException;
use App\Modules\Payments\Exceptions\PaymentException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class PaymentService
{
    public const MAX_GATEWAY_TRIES = 3;

    public const MAX_EXPIRY_SECONDS = 900;

    public function __construct(
        private PaymentGateway $gateway,
        private AuditLogger $audit,
    ) {}

    /**
     * @throws PaymentException
     */
    public function initiate(Order $order): Payment
    {
        $payment = Payment::query()->where('order_id', $order->id)->first();
        if ($payment === null) {
            if (! $order->isAwaitingPayment()) {
                throw PaymentException::orderNotPayable();
            }

            $payment = $this->createPayment($order);
        }

        if ($payment->isPaid() || ! $order->isAwaitingPayment()) {
            return $payment->load('latestAttempt');
        }

        return Cache::lock('payment-charge:'.$payment->id, 15)->block(10, function () use ($order, $payment): Payment {
            $attempt = $payment->latestAttempt()->first();

            if ($attempt !== null && $attempt->status === 'pending' && ! $attempt->isExpired()) {
                return $payment->setRelation('latestAttempt', $attempt);
            }

            if ($attempt !== null && $attempt->status === 'pending') {
                $attempt->forceFill(['status' => 'expired'])->save();
                $this->audit->record('payment', $payment->id, 'qris_expired', null, ['attempt_id' => $attempt->id]);
            }

            return $payment->setRelation('latestAttempt', $this->charge($order, $payment));
        });
    }

    public function cancelUnpaid(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $payment = Payment::query()->where('order_id', $order->id)->lockForUpdate()->first();
            if ($payment !== null && ! $payment->isPaid()) {
                PaymentAttempt::query()
                    ->where('payment_id', $payment->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                $payment->forceFill(['status' => 'failed'])->save();
            }

            app(CancelUnpaidOrder::class)->cancel($order);
        });
    }

    private function createPayment(Order $order): Payment
    {
        try {
            return DB::transaction(function () use ($order): Payment {
                $payment = new Payment;
                $payment->forceFill([
                    'order_id' => $order->id,
                    'payment_reference' => 'PAY-'.strtoupper(Str::random(16)),
                    'idempotency_key' => 'pay-init-'.$order->id,
                    'amount' => $order->grand_total_amount,
                    'status' => 'pending',
                ])->save();

                return $payment;
            });
        } catch (UniqueConstraintViolationException) {
            return Payment::query()->where('order_id', $order->id)->firstOrFail();
        }
    }

    /**
     * @throws PaymentException
     */
    private function charge(Order $order, Payment $payment): PaymentAttempt
    {
        $request = new PaymentChargeRequest(
            reference: $payment->payment_reference,
            orderNumber: $order->order_number,
            amount: (int) $payment->amount,
            expiresInSeconds: min(self::MAX_EXPIRY_SECONDS, (int) config('services.qris.expiry_seconds', self::MAX_EXPIRY_SECONDS)),
            splits: $this->splits($order),
        );

        try {
            /** @var QrisCharge $charge */
            $charge = retry(
                self::MAX_GATEWAY_TRIES,
                fn (): QrisCharge => $this->gateway->createQrisCharge($request),
                200,
                fn (Throwable $e): bool => $e instanceof GatewayUnavailableException,
            );
        } catch (GatewayUnavailableException) {
            $this->audit->record('payment', $payment->id, 'gateway_unavailable', null, [
                'tries' => self::MAX_GATEWAY_TRIES,
                'provider' => $this->gateway->name(),
            ]);

            throw PaymentException::gatewayUnavailable();
        }

        $attempt = new PaymentAttempt;
        $attempt->forceFill([
            'payment_id' => $payment->id,
            'provider_reference' => $charge->providerReference,
            'qris_payload' => $charge->qrisPayload,
            'status' => 'pending',
            'expires_at' => $charge->expiresAt,
        ])->save();

        $this->audit->record('payment', $payment->id, 'initiated', null, [
            'order_id' => $order->id,
            'amount' => $payment->amount,
            'provider' => $this->gateway->name(),
            'splits' => $request->splits,
        ]);

        return $attempt;
    }

    /**
     * Rincian split per tenant untuk PetMarket: mencakup ongkos kirim (`shipping_cost`)
     * agar total gross seluruh tenant sama persis dengan `orders.grand_total_amount`.
     *
     * @return list<array{tenant_id: int, gross: int, commission: int, net: int}>
     */
    private function splits(Order $order): array
    {
        $splits = [];

        $tenantOrders = TenantOrder::query()
            ->withoutGlobalScope('tenant')
            ->where('order_id', $order->id)
            ->orderBy('tenant_id')
            ->get();

        foreach ($tenantOrders as $tenantOrder) {
            $splits[] = [
                'tenant_id' => (int) $tenantOrder->tenant_id,
                'gross' => (int) ($tenantOrder->subtotal_amount + $tenantOrder->tax_amount + $tenantOrder->service_fee_amount + $tenantOrder->shipping_cost),
                'commission' => (int) $tenantOrder->commission_amount,
                'net' => (int) $tenantOrder->net_amount,
            ];
        }

        return $splits;
    }

    /**
     * @throws PaymentException
     */
    public function confirmSandbox(Payment $payment): Payment
    {
        if (! $this->gateway->isSandbox()) {
            throw PaymentException::sandboxOnly();
        }

        $payment->refresh();
        if ($payment->isPaid()) {
            return $payment;
        }

        $attempt = $payment->latestAttempt()->first();
        if ($attempt !== null && $attempt->isExpired()) {
            throw PaymentException::attemptExpired();
        }

        DB::transaction(function () use ($payment, $attempt): void {
            if ($attempt !== null) {
                $attempt->forceFill(['status' => 'success'])->save();
            }

            $payment->forceFill(['status' => 'paid', 'settled_at' => now()])->save();

            Order::query()->whereKey($payment->order_id)->update(['status' => 'paid']);

            // Teruskan sub-pesanan tiap toko ke status Pesanan Baru (FR-ORD-01)
            TenantOrder::query()
                ->withoutGlobalScope('tenant')
                ->where('order_id', $payment->order_id)
                ->update(['status' => 'new_order']);

            DB::table('payment_events')->insert([
                'payment_id' => $payment->id,
                'payment_attempt_id' => $attempt?->id,
                'provider_event_id' => 'SBX-'.$payment->payment_reference,
                'signature' => null,
                'payload' => json_encode(['simulated' => true, 'amount' => $payment->amount], JSON_THROW_ON_ERROR),
                'result' => 'verified',
                'received_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record('payment', $payment->id, 'paid_sandbox', null, [
                'amount' => $payment->amount,
            ]);
        });

        return $payment;
    }
}

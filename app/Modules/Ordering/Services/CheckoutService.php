<?php

namespace App\Modules\Ordering\Services;

use App\Models\CommissionScheme;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TenantOrder;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Ordering\Data\CartLine;
use App\Modules\Ordering\Data\CheckoutDetails;
use App\Modules\Ordering\Data\CheckoutResult;
use App\Modules\Ordering\Exceptions\CheckoutException;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class CheckoutService
{
    private const TAX_RATE = 0.10;

    private const SERVICE_FEE_RATE = 0.02;

    public function __construct(
        private CartService $cart,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, string>|null  $selectedLineKeys  Null = checkout semua item di keranjang; Array = hanya item yang dicentang
     *
     * @throws CheckoutException
     */
    public function checkout(
        CustomerSession $session,
        string $idempotencyKey,
        ?CarbonInterface $scheduledAt = null,
        ?CheckoutDetails $details = null,
        ?array $selectedLineKeys = null,
    ): CheckoutResult {
        $existing = Order::query()->where('checkout_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return new CheckoutResult($existing, null, isReplay: true);
        }

        $view = $this->cart->view($session);
        if ($view->isEmpty()) {
            throw CheckoutException::emptyCart();
        }

        // Filter hanya item yang dipilih (jika user mencentang satuan/sebagian)
        $linesToCheckout = $selectedLineKeys !== null
            ? array_values(array_filter(
                $view->lines,
                static fn (CartLine $line): bool => in_array($line->lineKey, $selectedLineKeys, true)
            ))
            : $view->lines;

        if ($linesToCheckout === []) {
            throw CheckoutException::emptyCart();
        }

        foreach ($linesToCheckout as $line) {
            if (! $line->available) {
                throw CheckoutException::cartNotOrderable();
            }
        }

        // Kelompokkan baris per tenant (toko)
        /** @var array<int, list<CartLine>> $groups */
        $groups = [];
        foreach ($linesToCheckout as $line) {
            $groups[$line->tenantId][] = $line;
        }

        $serviceMode = $scheduledAt !== null
            ? CheckoutDetails::PICKUP
            : ($details !== null ? $details->serviceMode : CheckoutDetails::DELIVERY);
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $authUserId = auth()->id();
        $userId = $session->user_id ?? (is_numeric($authUserId) ? (int) $authUserId : null);

        try {
            $order = DB::transaction(function () use (
                $session,
                $groups,
                $idempotencyKey,
                $tokenHash,
                $scheduledAt,
                $serviceMode,
                $details,
                $userId
            ): Order {
                $customerSnapshot = array_filter([
                    'session_id' => $session->id,
                    'name' => $details?->customerName,
                    'whatsapp' => $details?->whatsapp,
                    'address' => $details?->deliveryAddress,
                ], static fn ($value): bool => $value !== null && $value !== '');

                $placedAt = now();

                $order = new Order;
                $order->forceFill([
                    'public_id' => (string) Str::uuid(),
                    'order_number' => $this->orderNumber(),
                    'user_id' => $userId,
                    'customer_session_id' => $session->id,
                    'checkout_key' => $idempotencyKey,
                    'tracking_token_hash' => $tokenHash,
                    'status' => 'awaiting_payment',
                    'service_mode' => $serviceMode,
                    'subtotal_amount' => 0,
                    'tax_amount' => 0,
                    'service_fee_amount' => 0,
                    'grand_total_amount' => 0,
                    'shipping_address' => $details?->deliveryAddress,
                    'customer_snapshot' => $customerSnapshot,
                    'placed_at' => $placedAt,
                ])->save();

                $orderSubtotal = 0;
                $orderTax = 0;
                $orderFee = 0;

                foreach ($groups as $tenantId => $lines) {
                    $commission = $this->activeCommission($tenantId);
                    $rate = $commission !== null ? (float) $commission->commission_rate : 0.10;
                    $tenantSubtotal = array_sum(array_map(static fn (CartLine $l): int => $l->lineTotal, $lines));
                    $tenantTax = (int) round($tenantSubtotal * self::TAX_RATE);
                    $tenantFee = (int) round($tenantSubtotal * self::SERVICE_FEE_RATE);
                    $commissionAmount = (int) round($tenantSubtotal * $rate);
                    $netAmount = $tenantSubtotal - $commissionAmount;
                    $tenantGrandTotal = $tenantSubtotal + $tenantTax + $tenantFee;

                    $tenantOrder = new TenantOrder;
                    $tenantOrder->forceFill([
                        'public_id' => (string) Str::uuid(),
                        'order_number' => $order->order_number.'-'.strtoupper(Str::random(4)),
                        'order_id' => $order->id,
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'commission_id' => $commission?->id,
                        'status' => $scheduledAt !== null ? 'scheduled' : 'pending',
                        'scheduled_at' => $scheduledAt,
                        'commission_rate_snapshot' => $commission !== null ? $commission->commission_rate : '0.1000',
                        'subtotal_amount' => $tenantSubtotal,
                        'tax_amount' => $tenantTax,
                        'service_fee_amount' => $tenantFee,
                        'commission_amount' => $commissionAmount,
                        'net_amount' => $netAmount,
                        'grand_total_amount' => $tenantGrandTotal,
                        'shipping_address' => $details?->deliveryAddress,
                        'customer_snapshot' => $customerSnapshot,
                        'placed_at' => $placedAt,
                    ])->save();

                    // Urutkan product ID sebelum lock untuk mencegah deadlock
                    usort($lines, static fn (CartLine $a, CartLine $b): int => $a->menuId <=> $b->menuId);

                    foreach ($lines as $line) {
                        $this->reserveStock($tenantId, $line);
                        $this->persistItem($tenantId, $tenantOrder->id, $line);
                    }

                    $this->audit->record('tenant_order', $tenantOrder->id, 'placed', null, [
                        'order_id' => $order->id,
                        'subtotal' => $tenantSubtotal,
                        'commission' => $commissionAmount,
                        'net' => $netAmount,
                    ], $tenantId);

                    $orderSubtotal += $tenantSubtotal;
                    $orderTax += $tenantTax;
                    $orderFee += $tenantFee;
                }

                $order->forceFill([
                    'subtotal_amount' => $orderSubtotal,
                    'tax_amount' => $orderTax,
                    'service_fee_amount' => $orderFee,
                    'grand_total_amount' => $orderSubtotal + $orderTax + $orderFee,
                ])->save();

                return $order;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            $order = Order::query()->where('checkout_key', $idempotencyKey)->firstOrFail();

            return new CheckoutResult($order, null, isReplay: true);
        }

        // Hapus HANYA item yang sudah di-checkout; item lain di keranjang tetap aman
        foreach ($linesToCheckout as $line) {
            $this->cart->remove($session, $line->lineKey);
        }

        return new CheckoutResult($order, $plainToken);
    }

    private function reserveStock(int $tenantId, CartLine $line): void
    {
        /** @var Product|null $product */
        $product = Product::query()
            ->withoutGlobalScope('tenant')
            ->where('id', $line->menuId)
            ->where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->first();

        if ($product === null || ! $product->is_available || $product->stock_qty < $line->quantity) {
            throw CheckoutException::insufficientStock();
        }

        $product->decrement('stock_qty', $line->quantity);
    }

    private function persistItem(int $tenantId, int $tenantOrderId, CartLine $line): void
    {
        $item = new OrderItem;
        $item->forceFill([
            'tenant_id' => $tenantId,
            'tenant_order_id' => $tenantOrderId,
            'product_id' => $line->menuId,
            'name_snapshot' => $line->name,
            'unit_price_snapshot' => $line->unitPrice,
            'quantity' => $line->quantity,
            'modifier_total' => $line->modifierTotal,
            'line_total' => $line->lineTotal,
            'note' => $line->note,
        ])->save();

        if (! Schema::hasTable('order_item_modifiers')) {
            return;
        }

        foreach ($line->modifiers as $modifier) {
            $now = now();
            DB::table('order_item_modifiers')->insert([
                'order_item_id' => $item->id,
                'name' => $modifier['name'],
                'price_delta' => $modifier['price_delta'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function activeCommission(int $tenantId): ?CommissionScheme
    {
        $now = now();

        return CommissionScheme::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where(static function ($query) use ($now): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(static function ($query) use ($now): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', $now);
            })
            ->orderByDesc('valid_from')
            ->first();
    }

    private function orderNumber(): string
    {
        return 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
    }
}

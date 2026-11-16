<?php

namespace App\Modules\Ordering\Services;

use App\Models\CustomerSession;
use App\Models\Product;
use App\Modules\Ordering\Data\CartLine;
use App\Modules\Ordering\Data\CartView;
use App\Modules\Ordering\Exceptions\CartException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

final class CartService
{
    private const KEY_PREFIX = 'cart:';

    private const MAX_QTY_PER_LINE = 20;

    private const MAX_LINES = 50;

    private const FALLBACK_TTL_SECONDS = 14400;

    public const NOTE_MAX_LENGTH = 200;

    /**
     * @param  array<int, int>  $optionIds
     */
    public function add(CustomerSession $session, int $productId, int $quantity = 1, array $optionIds = [], ?string $note = null): void
    {
        if ($quantity < 1) {
            throw CartException::invalidQuantity();
        }

        /** @var Product|null $product */
       $product = Product::query()
    ->withoutGlobalScope('tenant')
    ->with('tenant')
    ->where('id', $productId)
    ->where('is_available', true)
    ->where('stock_qty', '>', 0)
    ->whereHas('tenant', fn ($query) => $query->where('status', 'active'))
    ->first();
        if ($product === null) {
            throw CartException::menuNotOrderable();
        }

        $cleanNote = $this->sanitizeNote($note);
        $lineKey = $this->lineKey($productId, $optionIds, $cleanNote);

        $items = $this->load($session);
        $existingQty = isset($items[$lineKey]) ? (int) $items[$lineKey]['quantity'] : 0;
        $newQty = min($existingQty + $quantity, self::MAX_QTY_PER_LINE);

        if (! isset($items[$lineKey]) && count($items) >= self::MAX_LINES) {
            throw CartException::tooManyLines();
        }

        $items[$lineKey] = [
            'line_key' => $lineKey,
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id ?? 0,
            'tenant_name' => (string) ($product->tenant->name ?? 'Tenant'),
            'quantity' => $newQty,
            'option_ids' => $optionIds,
            'note' => $cleanNote,
            'price_at_add' => (int) $product->base_price,
            'name_at_add' => (string) $product->name,
        ];

        $this->persist($session, $items);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function lineKey(int $productId, array $optionIds, ?string $note = null): string
    {
        return substr(hash('sha256', $productId.'|'.implode(',', $optionIds).'|'.($note ?? '')), 0, 24);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function load(CustomerSession $session): array
    {
        $raw = Redis::get($this->key($session));
        if (! is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['items']) || ! is_array($decoded['items'])) {
            return [];
        }
        $items = [];
        foreach ($decoded['items'] as $item) {
            if (is_array($item) && isset($item['line_key'])) {
                $items[(string) $item['line_key']] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    private function persist(CustomerSession $session, array $items): void
    {
        if ($items === []) {
            Redis::del($this->key($session));

            return;
        }
        $payload = json_encode(['items' => array_values($items)], JSON_THROW_ON_ERROR);
        Redis::setex($this->key($session), $this->ttlSeconds($session), $payload);
    }

    private function ttlSeconds(CustomerSession $session): int
    {
        if ($session->expires_at !== null) {
            $remaining = now()->diffInSeconds($session->expires_at, false);
            if ($remaining > 0) {
                return (int) $remaining;
            }
        }

        return self::FALLBACK_TTL_SECONDS;
    }

    private function key(CustomerSession $session): string
    {
        return self::KEY_PREFIX.$session->getKey();
    }

    private function sanitizeNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags($note)));
        if (mb_strlen($clean) > self::NOTE_MAX_LENGTH) {
            throw CartException::noteTooLong(self::NOTE_MAX_LENGTH);
        }

        return $clean === '' ? null : $clean;
    }

    /**
     * @return Collection<int, Model>
     */
    public function modifierGroupsFor(CustomerSession $session, int $productId): Collection
    {
        return new Collection;
    }

    public function view(CustomerSession $session): CartView
    {
        $items = $this->load($session);
        $lines = [];
        $subtotal = 0;
        $totalQty = 0;
        $hasBlockingIssues = false;

        foreach ($items as $item) {
            /** @var Product|null $product */
           $product = Product::query()
    ->withoutGlobalScope('tenant')
    ->with('tenant')
    ->where('id', $item['product_id'])
    ->whereHas('tenant', fn ($query) => $query->where('status', 'active'))
    ->first();
            $qty = (int) $item['quantity'];

            $available = $product !== null && $product->is_available && $product->stock_qty >= $qty;
            $issues = [];
            if ($product === null || ! $product->is_available) {
                $issues[] = 'product_unavailable';
            } elseif ($product->stock_qty < $qty) {
                $issues[] = 'insufficient_stock';
            }

            $unitPrice = $product !== null ? (int) $product->base_price : (int) $item['price_at_add'];
            $modifierTotal = 0;
            $lineTotal = ($unitPrice + $modifierTotal) * $qty;

            if (! $available) {
                $hasBlockingIssues = true;
            } else {
                $subtotal += $lineTotal;
            }

            $totalQty += $qty;

            $tenantName = $item['tenant_name'] ?? ($product !== null && $product->tenant !== null ? $product->tenant->name : 'Tenant');

            $lines[] = new CartLine(
                lineKey: (string) $item['line_key'],
                menuId: (int) $item['product_id'],
                tenantId: (int) ($item['tenant_id'] ?? 0),
                tenantName: (string) $tenantName,
                name: (string) $item['name_at_add'],
                quantity: $qty,
                unitPrice: $unitPrice,
                priceAtAdd: (int) $item['price_at_add'],
                modifiers: [],
                modifierTotal: $modifierTotal,
                lineTotal: $lineTotal,
                available: $available,
                issues: $issues,
                note: isset($item['note']) ? (string) $item['note'] : null,
            );
        }

        $taxAmount = (int) round($subtotal * 0.10);
        $serviceFeeAmount = (int) round($subtotal * 0.02);

        return new CartView(
            lines: $lines,
            subtotal: $subtotal,
            taxAmount: $taxAmount,
            serviceFeeAmount: $serviceFeeAmount,
            totalQuantity: $totalQty,
            hasBlockingIssues: $hasBlockingIssues
        );
    }

    public function setQuantity(CustomerSession $session, string $lineKey, int $quantity): void
    {
        $items = $this->load($session);
        if (! isset($items[$lineKey])) {
            return;
        }

        if ($quantity <= 0) {
            unset($items[$lineKey]);
        } else {
            $items[$lineKey]['quantity'] = min($quantity, self::MAX_QTY_PER_LINE);
        }

        $this->persist($session, $items);
    }

    public function remove(CustomerSession $session, string $lineKey): void
    {
        $this->setQuantity($session, $lineKey, 0);
    }

    public function clear(CustomerSession $session): void
    {
        Redis::del($this->key($session));
    }
}

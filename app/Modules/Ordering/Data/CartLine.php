<?php

namespace App\Modules\Ordering\Data;

final readonly class CartLine
{
    /**
     * @param  list<array{id: int, name: string, price_delta: int}>  $modifiers
     * @param  list<string>  $issues
     */
    public function __construct(
        public string $lineKey,
        public int $menuId,
        public int $tenantId,
        public string $tenantName,
        public string $name,
        public int $quantity,
        public int $unitPrice,
        public int $priceAtAdd,
        public array $modifiers,
        public int $modifierTotal,
        public int $lineTotal,
        public bool $available,
        public array $issues,
        public ?string $note = null,
    ) {}

    public function priceChanged(): bool
    {
        return in_array('price_changed', $this->issues, true);
    }
}

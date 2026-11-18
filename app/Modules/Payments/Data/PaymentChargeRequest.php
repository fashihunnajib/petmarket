<?php

namespace App\Modules\Payments\Data;

/**
 * @phpstan-type Split array{tenant_id: int, gross: int, commission: int, net: int}
 */
final readonly class PaymentChargeRequest
{
    /**
     * @param  list<Split>  $splits
     */
    public function __construct(
        public string $reference,
        public string $orderNumber,
        public int $amount,
        public int $expiresInSeconds = 900,
        public array $splits = [],
    ) {}
}

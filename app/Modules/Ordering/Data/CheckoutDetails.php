<?php

namespace App\Modules\Ordering\Data;

final readonly class CheckoutDetails
{
    public const DELIVERY = 'delivery';

    public const PICKUP = 'pickup';

    public function __construct(
        public string $serviceMode,
        public string $customerName,
        public string $whatsapp,
        public ?string $deliveryAddress = null,
    ) {}

    public function isPickup(): bool
    {
        return $this->serviceMode === self::PICKUP;
    }

    public static function normalizeWhatsapp(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}

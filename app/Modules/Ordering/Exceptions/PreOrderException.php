<?php

namespace App\Modules\Ordering\Exceptions;

use Carbon\CarbonImmutable;
use RuntimeException;

final class PreOrderException extends RuntimeException
{
    /** @var list<CarbonImmutable> */
    public array $alternatives = [];

    /**
     * @param  list<CarbonImmutable>  $alternatives
     */
    public static function withAlternatives(string $message, array $alternatives): self
    {
        $exception = new self($message);
        $exception->alternatives = $alternatives;

        return $exception;
    }

    public static function notEnabled(string $tenantName): self
    {
        return new self("{$tenantName} belum menerima pre-order.");
    }
}

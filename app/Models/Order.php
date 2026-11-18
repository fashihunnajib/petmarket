<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'integer',
            'tax_amount' => 'integer',
            'shipping_cost' => 'integer',
            'service_fee_amount' => 'integer',
            'grand_total_amount' => 'integer',
            'customer_snapshot' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TenantOrder, $this> */
    public function tenantOrders(): HasMany
    {
        return $this->hasMany(TenantOrder::class);
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isAwaitingPayment(): bool
    {
        return in_array($this->status, ['awaiting_payment', 'pending_payment'], true);
    }
}

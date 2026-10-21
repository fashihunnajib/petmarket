<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Order induk — platform-scoped: satu checkout bisa lintas tenant.
 */
class TenantOrder extends Model
{
    protected $fillable = ['order_number', 'checkout_key', 'status'];

    protected function casts(): array
    {
        return [
            'public_id' => 'string',
            'commission_rate_snapshot' => 'decimal:4',
            'subtotal_amount' => 'integer',
            'tax_amount' => 'integer',
            'shipping_cost' => 'integer',
            'service_fee_amount' => 'integer',
            'commission_amount' => 'integer',
            'net_amount' => 'integer',
            'grand_total_amount' => 'integer',
            'customer_snapshot' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasOne<OrderDelivery, $this> */
    public function delivery(): HasOne
    {
        return $this->hasOne(OrderDelivery::class, 'tenant_order_id');
    }
}

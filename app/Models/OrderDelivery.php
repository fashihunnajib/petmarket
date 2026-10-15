<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDelivery extends Model
{
    // tenant_id diisi otomatis via BelongsToTenant trait saat event creating
    protected $fillable = [
        'tenant_order_id',
        'delivery_type',
        'expedition_name',
        'tracking_number',
        'distance_km',
        'delivery_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
        ];
    }

    public function isSellerCourier(): bool
    {
        return $this->delivery_type === 'seller_courier_meetup';
    }

    public function isRegularExpedition(): bool
    {
        return $this->delivery_type === 'regular_expedition';
    }

    /**
     * Relasi ke induk pesanan toko
     *
     * @return BelongsTo<TenantOrder, $this>
     */
    public function tenantOrder(): BelongsTo
    {
        return $this->belongsTo(TenantOrder::class, 'tenant_order_id');
    }
}
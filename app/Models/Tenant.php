<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'slug',
        'name',
        'tax_rate',
        'service_fee_rate',
        'status',
        'latitude',
        'longitude',
        'max_delivery_radius_km',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:4',
            'service_fee_rate' => 'decimal:4',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'max_delivery_radius_km' => 'decimal:2',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** @return HasOne<TenantBalance, $this> */
    public function balance(): HasOne
    {
        return $this->hasOne(TenantBalance::class, 'tenant_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    /** @return HasMany<ProductCategory, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'tenant_id');
    }

    /** @return HasMany<TenantOrder, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(TenantOrder::class, 'tenant_id');
    }
}

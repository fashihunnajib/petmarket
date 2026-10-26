<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'base_price', 'stock_qty', 'is_available', 'description', 'photo_path'];

    /**
     * Scope untuk produk yang siap dijual (tersedia dan stok ada).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSellable(Builder $query): void
    {
        $query->where('is_available', true)->where('stock_qty', '>', 0);
    }

    // /**
    //  * Scope untuk produk yang siap dijual (tersedia dan stok ada).
    //  *
    //  * @param  Builder<$this>  $query
    //  */
    // public function scopeSellable(Builder $query): void
    // {
    //     $query->where('is_available', true)->where('stock_qty', '>', 0);
    // }

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'stock_qty' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return HasOne<ProductAnimal, $this> */
    public function animalDetail(): HasOne
    {
        return $this->hasOne(ProductAnimal::class, 'product_id');
    }

    /** @return HasOne<ProductGood, $this> */
    public function goodDetail(): HasOne
    {
        return $this->hasOne(ProductGood::class, 'product_id');
    }
}

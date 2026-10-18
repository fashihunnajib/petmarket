<?php

namespace App\Models;

use Database\Factories\ProductGoodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductGood extends Model
{
    /** @use HasFactory<ProductGoodFactory> */
    use HasFactory;

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected $fillable = ['product_id', 'tenant_id', 'weight_gram', 'dimension_cm', 'expired_date'];

    protected function casts(): array
    {
        return [
            'weight_gram' => 'integer',
            'expired_date' => 'date',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

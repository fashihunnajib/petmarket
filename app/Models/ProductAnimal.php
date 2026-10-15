<?php

namespace App\Models;

use Database\Factories\ProductAnimalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnimal extends Model
{
    /** @use HasFactory<ProductAnimalFactory> */
    use HasFactory;

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected $fillable = ['product_id', 'tenant_id', 'breed', 'age_months', 'gender', 'vaccination_status', 'weight_kg', 'health_history'];

    protected function casts(): array
    {
        return [
            'age_months' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

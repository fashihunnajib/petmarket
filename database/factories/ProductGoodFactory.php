<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductGood;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductGood> */
class ProductGoodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(['type' => 'good']),
            // Ambil tenant_id dari objek Product yang berelasi untuk menjaga integritas Composite FK
            'tenant_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->tenant_id,
            'weight_gram' => fake()->numberBetween(500, 10000),
            'dimension_cm' => fake()->randomElement(['10x5x3', '20x15x10', '30x20x15']),
            'expired_date' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
        ];
    }
}

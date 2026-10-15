<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            // Kategori dibuat untuk tenant yang sama (composite FK) — closure menerima atribut terselesaikan.
            'category_id' => fn (array $attributes) => ProductCategory::factory()
                ->create(['tenant_id' => $attributes['tenant_id']])->id,
            'name' => fake()->unique()->word(),
            'base_price' => fake()->numberBetween(8, 60) * 1000,
            'stock_qty' => 50,
            'is_available' => true,
            'description' => fake()->paragraph(),
            'photo_path' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ProductAnimal;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductAnimal> */
class ProductAnimalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(['type' => 'animal']),
            // Ambil tenant_id dari objek Product yang berelasi untuk menjaga integritas Composite FK
            'tenant_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->tenant_id,
            'breed' => fake()->word(),
            'age_months' => fake()->numberBetween(1, 24),
            'gender' => fake()->randomElement(['male', 'female']),
            'vaccination_status' => fake()->randomElement(['not vaccinated', 'partially vaccinated', 'fully vaccinated']),
            'weight_kg' => fake()->numberBetween(1, 10),
            'health_history' => fake()->paragraph(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'code' => strtoupper(Str::random(6)),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => Str::title($name),
            'tax_rate' => 0.1000,
            'service_fee_rate' => 0.0200,
            'status' => 'active',
            'latitude' => fake()->latitude(-6.95, -6.85),
            'longitude' => fake()->longitude(107.55, 107.65),
            'max_delivery_radius_km' => 10.00,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }
}

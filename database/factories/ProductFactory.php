<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->bothify('SKU-####'),
            'unit_price' => fake()->randomFloat(2, 1, 500),
            'tax_percentage' => fake()->randomElement([0, 5, 8.25, 10, 18]),
            'stock_on_hand' => fake()->numberBetween(0, 100),
        ];
    }
}

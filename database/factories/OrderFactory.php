<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'subtotal' => fake()->randomFloat(2, 10, 500),
            'tax_total' => fake()->randomFloat(2, 0, 50),
            'grand_total' => fake()->randomFloat(2, 10, 550),
        ];
    }
}

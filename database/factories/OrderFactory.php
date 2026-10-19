<?php

namespace Database\Factories;

use App\Models\Event;
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
            'event_id' => Event::factory(),
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_phone' => fake()->numerify('08##########'),
            'subtotal' => 150000,
            'discount_amount' => 0,
            'total_amount' => 150000,
            'payment_method' => 'manual_transfer',
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'expires_at' => now()->addHours(2),
            'paid_at' => null,
        ];
    }
}

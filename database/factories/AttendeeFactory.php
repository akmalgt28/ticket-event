<?php

namespace Database\Factories;

use App\Models\Attendee;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendee>
 */
class AttendeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'metadata' => null,
        ];
    }
}

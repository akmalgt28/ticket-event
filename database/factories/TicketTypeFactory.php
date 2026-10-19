<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
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
            'name' => fake()->randomElement(['Regular', 'VIP', 'Early Bird']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50000, 1000000),
            'quota' => 100,
            'sold_count' => 0,
            'sale_start_at' => now(),
            'sale_end_at' => now()->addDays(10),
            'status' => 'active',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraphs(3, true),
            'banner_path' => null,
            'start_at' => now()->addDays(14)->setTime(9, 0),
            'end_at' => now()->addDays(14)->setTime(17, 0),
            'location_name' => fake()->company().' Hall',
            'location_address' => fake()->address(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->numerify('08##########'),
            'status' => 'draft',
            'published_at' => null,
        ];
    }
}

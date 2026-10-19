<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
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
            'order_item_id' => OrderItem::factory(),
            'attendee_id' => null,
            'ticket_number' => 'TKT-'.fake()->unique()->numerify('########'),
            'qr_token_hash' => hash('sha256', fake()->unique()->uuid()),
            'status' => 'issued',
            'checked_in_at' => null,
            'checked_in_by' => null,
        ];
    }
}

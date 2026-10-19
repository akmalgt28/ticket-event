<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_pending_order_and_reserve_quota(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticketType = TicketType::factory()->for($event)->create([
            'price' => '125.50',
            'quota' => 5,
        ]);

        $response = $this->postJson('/api/orders', [
            'event_id' => $event->id,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '08123456789',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 2],
            ],
            'attendees' => [
                [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'phone' => '08123456789',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.order_status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.total_amount', '251.00');
        $this->assertDatabaseHas('orders', [
            'event_id' => $event->id,
            'customer_email' => 'jane@example.com',
            'total_amount' => '251.00',
        ]);
        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'sold_count' => 2,
        ]);
        $this->assertDatabaseCount('attendees', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_guest_checkout_rejects_unpublished_event(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);
        $ticketType = TicketType::factory()->for($event)->create();

        $response = $this->postJson('/api/orders', [
            'event_id' => $event->id,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '08123456789',
            'items' => [['ticket_type_id' => $ticketType->id, 'quantity' => 1]],
            'attendees' => [[
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => '08123456789',
            ]],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('event_id');
        $this->assertDatabaseCount('orders', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\TicketIssuanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_gets_hashed_ticket_token_and_staff_can_check_in(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['price' => '100.00']);
        $order = Order::factory()->for($event)->create([
            'order_status' => OrderStatus::Paid,
            'total_amount' => '100.00',
        ]);
        $orderItem = OrderItem::factory()->for($order)->for($ticketType, 'ticketType')->create([
            'unit_price' => '100.00',
            'subtotal' => '100.00',
            'quantity' => 1,
        ]);
        $attendee = Attendee::factory()->for($order)->create();
        $issued = app(TicketIssuanceService::class)->issue($order);
        $ticket = $issued['tickets']->first();
        $token = $issued['qr_tokens'][$ticket->id];

        $this->assertNotSame($token, $ticket->qr_token_hash);
        $this->assertSame($orderItem->id, $ticket->order_item_id);
        $this->assertSame($attendee->id, $ticket->attendee_id);

        $response = $this->actingAs($staff)->postJson('/check-in', ['token' => $token]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'used')
            ->assertJsonPath('data.attendee.name', $attendee->name)
            ->assertJsonPath('data.event.title', $event->title)
            ->assertJsonMissingPath('data.qr_token_hash');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'used',
            'checked_in_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->postJson('/check-in', ['token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_ticket_from_expired_order_cannot_be_checked_in(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $order = Order::factory()->create(['order_status' => OrderStatus::Expired]);
        $token = 'expired-ticket-token';
        Ticket::factory()->for($order)->create([
            'qr_token_hash' => hash('sha256', $token),
            'status' => 'issued',
        ]);

        $this->actingAs($staff)
            ->postJson('/check-in', ['token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_staff_can_check_in_using_ticket_number_fallback(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $ticket = Ticket::factory()->for(Order::factory()->create(['order_status' => OrderStatus::Paid]))->create(['ticket_number' => 'TKT-MANUAL-001']);

        $this->actingAs($staff)
            ->postJson('/check-in', ['ticket_number' => $ticket->ticket_number])
            ->assertOk()
            ->assertJsonPath('data.ticket_number', 'TKT-MANUAL-001')
            ->assertJsonPath('data.status', 'used');
    }

    public function test_unknown_ticket_token_is_rejected(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->postJson('/check-in', ['token' => 'unknown-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_staff_can_open_check_in_page(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get('/check-in')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Staff/CheckIn'));
    }

    public function test_non_staff_cannot_open_check_in_page(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/check-in')->assertForbidden();
    }
}

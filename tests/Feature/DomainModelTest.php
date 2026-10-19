<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Enums\TicketTypeStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_domain_relationships_are_persisted(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $order = Order::factory()->for($event)->create();
        $orderItem = OrderItem::factory()->for($order)->for($ticketType, 'ticketType')->create([
            'ticket_name_snapshot' => $ticketType->name,
            'unit_price' => $ticketType->price,
            'subtotal' => $ticketType->price,
        ]);
        $attendee = Attendee::factory()->for($order)->create();
        $ticket = Ticket::factory()->for($order)->for($orderItem, 'orderItem')->for($attendee)->create();

        $this->assertTrue($event->ticketTypes->contains($ticketType));
        $this->assertTrue($order->items->contains($orderItem));
        $this->assertTrue($order->attendees->contains($attendee));
        $this->assertSame($order->id, $ticket->order->id);
        $this->assertSame($ticketType->id, $orderItem->ticketType->id);
    }

    public function test_domain_statuses_are_backed_enums_with_allowed_transitions(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);
        $order = Order::factory()->create([
            'order_status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $ticketType = TicketType::factory()->create(['status' => TicketTypeStatus::Active]);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Issued]);

        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame(OrderStatus::Pending, $order->order_status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(TicketTypeStatus::Active, $ticketType->status);
        $this->assertSame(TicketStatus::Issued, $ticket->status);
        $this->assertTrue(EventStatus::Draft->canTransitionTo(EventStatus::Published));
        $this->assertFalse(EventStatus::Published->canTransitionTo(EventStatus::Draft));
        $this->assertTrue(OrderStatus::Paid->canTransitionTo(OrderStatus::Refunded));
        $this->assertFalse(TicketStatus::Used->canTransitionTo(TicketStatus::Used));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Enums\TicketTypeStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_metrics(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $publishedEvent = Event::factory()->create([
            'status' => EventStatus::Published,
            'start_at' => now()->addDay(),
        ]);
        Event::factory()->create(['status' => EventStatus::Draft]);
        $ticketType = TicketType::factory()->for($publishedEvent)->create([
            'quota' => 100,
            'sold_count' => 4,
            'status' => TicketTypeStatus::Active,
        ]);
        $paidOrder = Order::factory()->for($publishedEvent)->create([
            'payment_status' => PaymentStatus::Paid,
            'total_amount' => '250000.00',
        ]);
        Order::factory()->for($publishedEvent)->create(['payment_status' => PaymentStatus::Pending]);
        $orderItem = OrderItem::factory()->for($paidOrder)->for($ticketType)->create();
        Ticket::factory()->for($paidOrder)->create(['order_item_id' => $orderItem->id, 'status' => TicketStatus::Issued]);
        Ticket::factory()->for($paidOrder)->create(['order_item_id' => $orderItem->id, 'status' => TicketStatus::Used]);
        Ticket::factory()->for($paidOrder)->create(['order_item_id' => $orderItem->id, 'status' => TicketStatus::Cancelled]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('metrics.orders', 2)
            ->where('metrics.paid_orders', 1)
            ->where('metrics.tickets_sold', 2)
            ->where('metrics.tickets_remaining', 96)
            ->where('metrics.revenue', '250000.00')
            ->has('recentEvents', 1)
            ->where('recentEvents.0.title', $publishedEvent->title));
    }

    public function test_staff_cannot_view_organizer_dashboard(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)->get('/dashboard')->assertForbidden();
    }
}

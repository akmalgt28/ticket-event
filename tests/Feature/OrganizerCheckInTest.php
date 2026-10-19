<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_check_in_history_and_search_it(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create(['title' => 'Laravel Summit']);
        $order = Order::factory()->for($event)->create(['order_number' => 'ORD-CHECKIN']);
        $item = OrderItem::factory()->for($order)->for(TicketType::factory()->for($event), 'ticketType')->create();
        $attendee = Attendee::factory()->for($order)->create(['name' => 'Sari Wulandari']);
        Ticket::factory()->for($order)->for($item, 'orderItem')->for($attendee, 'attendee')->create([
            'status' => TicketStatus::Used,
            'checked_in_at' => now(),
            'checked_in_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/organizer/check-ins?search=Sari')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.attendee.name', 'Sari Wulandari');

        $this->actingAs($admin)
            ->get('/organizer/check-ins')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizer/CheckIns'));
    }

    public function test_staff_cannot_view_check_in_history(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)->get('/organizer/check-ins')->assertForbidden();
    }
}

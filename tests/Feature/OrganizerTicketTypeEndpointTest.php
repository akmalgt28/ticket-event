<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerTicketTypeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_ticket_type(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        $payload = [
            'name' => 'Regular',
            'description' => 'General admission',
            'price' => '125000.00',
            'quota' => 100,
            'sale_start_at' => '2026-11-01 00:00:00',
            'sale_end_at' => '2026-11-30 23:59:59',
        ];

        $response = $this->actingAs($admin)->postJson('/organizer/events/'.$event->id.'/ticket-types', $payload);

        $response->assertCreated()->assertJsonPath('data.name', 'Regular');
        $ticketType = TicketType::query()->where('name', 'Regular')->firstOrFail();

        $this->actingAs($admin)
            ->patchJson('/organizer/events/'.$event->id.'/ticket-types/'.$ticketType->id, [
                ...$payload,
                'name' => 'VIP',
                'price' => '200000.00',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'VIP');
    }

    public function test_ticket_type_cannot_be_accessed_through_another_event(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->actingAs($admin)
            ->getJson('/organizer/events/'.$otherEvent->id.'/ticket-types/'.$ticketType->id)
            ->assertNotFound();
    }

    public function test_staff_cannot_manage_ticket_types(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $event = Event::factory()->create();

        $this->actingAs($staff)
            ->postJson('/organizer/events/'.$event->id.'/ticket-types', [
                'name' => 'Blocked',
                'price' => 100,
                'quota' => 10,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_open_ticket_type_pages(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->actingAs($admin)->get('/organizer/events/'.$event->id.'/ticket-types/create')
            ->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/TicketTypeForm')->where('ticketType', null));
        $this->actingAs($admin)->get('/organizer/events/'.$event->id.'/ticket-types/'.$ticketType->id.'/edit')
            ->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/TicketTypeForm')->where('ticketType.id', $ticketType->id));
    }
}

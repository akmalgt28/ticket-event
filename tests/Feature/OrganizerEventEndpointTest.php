<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerEventEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_publish_and_close_event(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $payload = [
            'title' => 'Laravel Workshop',
            'description' => 'Learn Laravel.',
            'start_at' => '2026-12-01 09:00:00',
            'end_at' => '2026-12-01 17:00:00',
            'location_name' => 'Main Hall',
            'location_address' => 'Jakarta',
            'contact_email' => 'events@example.com',
            'contact_phone' => '08123456789',
        ];

        $response = $this->actingAs($admin)->postJson('/organizer/events', $payload);
        $response->assertCreated()->assertJsonPath('data.status', 'draft');
        $event = Event::query()->where('title', 'Laravel Workshop')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/organizer/events/'.$event->id.'/publish')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');

        TicketType::factory()->for($event)->create();
        $this->actingAs($admin)
            ->postJson('/organizer/events/'.$event->id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->actingAs($admin)
            ->postJson('/organizer/events/'.$event->id.'/close')
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_staff_cannot_manage_events(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->postJson('/organizer/events', [
                'title' => 'Blocked Event',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_open_events_inertia_page(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/organizer/events');

        $response->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/Events'));
    }

    public function test_admin_can_open_event_form_pages(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->get('/organizer/events/create')
            ->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/EventForm')->where('event', null));
        $this->actingAs($admin)->get('/organizer/events/'.$event->id.'/edit')
            ->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/EventForm')->where('event.id', $event->id));
    }
}

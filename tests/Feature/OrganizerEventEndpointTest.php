<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_event_banner_rejects_non_image_upload(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organizer/events', [
            'title' => 'Invalid Banner Event',
            'description' => 'Event with invalid banner.',
            'banner' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'start_at' => '2026-12-01 09:00:00',
            'end_at' => '2026-12-01 17:00:00',
            'location_name' => 'Main Hall',
            'location_address' => 'Jakarta',
        ]);

        $response->assertSessionHasErrors('banner');
        $this->assertDatabaseMissing('events', ['title' => 'Invalid Banner Event']);
    }

    public function test_admin_can_upload_event_banner(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->withHeaders(['X-Inertia' => 'true'])->post('/organizer/events', [
            'title' => 'Banner Event',
            'description' => 'Event with a banner.',
            'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 630),
            'start_at' => '2026-12-01 09:00:00',
            'end_at' => '2026-12-01 17:00:00',
            'location_name' => 'Main Hall',
            'location_address' => 'Jakarta',
        ]);

        $response->assertRedirect(route('organizer.events.index'));
        $event = Event::query()->where('title', 'Banner Event')->firstOrFail();

        $this->assertNotNull($event->banner_path);
        Storage::disk('public')->assertExists($event->banner_path);
    }

    public function test_event_banner_is_available_through_storage_fallback(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('events/fallback.jpg', 'poster-content');

        $response = $this->get('/storage/events/fallback.jpg');

        $response->assertOk();
        $this->assertFileExists(Storage::disk('public')->path('events/fallback.jpg'));
    }

    public function test_admin_can_update_event_with_multipart_method_spoofing(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create([
            'title' => 'Original Event',
            'description' => 'Original description.',
        ]);

        $response = $this->actingAs($admin)->withHeaders(['X-Inertia' => 'true'])->post('/organizer/events/'.$event->id, [
            '_method' => 'patch',
            'title' => 'Updated Event',
            'description' => 'Updated description.',
            'banner' => UploadedFile::fake()->image('updated-banner.jpg', 1200, 630),
            'start_at' => '2026-12-01 09:00:00',
            'end_at' => '2026-12-01 17:00:00',
            'location_name' => 'Updated Hall',
            'location_address' => 'Updated address',
        ]);

        $response->assertRedirect(route('organizer.events.index'));
        $event->refresh();

        $this->assertSame('Updated Event', $event->title);
        $this->assertSame('Updated description.', $event->description);
        $this->assertNotNull($event->banner_path);
        Storage::disk('public')->assertExists($event->banner_path);
    }
}

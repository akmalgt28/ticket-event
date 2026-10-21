<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicEventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_events_are_listed_with_active_ticket_types(): void
    {
        $publishedEvent = Event::factory()->create(['status' => 'published']);
        TicketType::factory()->for($publishedEvent)->create(['status' => 'active']);
        TicketType::factory()->for($publishedEvent)->create(['status' => 'inactive']);
        Event::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/events');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $publishedEvent->id)
            ->assertJsonCount(1, 'data.0.ticket_types');
    }

    public function test_draft_event_detail_returns_not_found(): void
    {
        $draftEvent = Event::factory()->create(['status' => 'draft']);

        $this->getJson('/api/events/'.$draftEvent->slug)->assertNotFound();
    }

    public function test_public_events_only_include_ticket_types_in_their_sale_period(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        $publishedEvent = Event::factory()->create(['status' => 'published']);
        $availableTicket = TicketType::factory()->for($publishedEvent)->create();
        TicketType::factory()->for($publishedEvent)->create([
            'name' => 'Future',
            'sale_start_at' => now()->addMinute(),
        ]);
        TicketType::factory()->for($publishedEvent)->create([
            'name' => 'Expired',
            'sale_end_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/events/'.$publishedEvent->slug);

        $response->assertOk()
            ->assertJsonCount(1, 'data.ticket_types')
            ->assertJsonPath('data.ticket_types.0.id', $availableTicket->id)
            ->assertJsonMissingPath('data.organizer_id');
    }

    public function test_guest_can_open_public_event_pages(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        TicketType::factory()->for($event)->create();

        $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page->component('Public/Events')->has('events.data', 1));
        $this->get('/events/'.$event->slug)->assertOk()->assertInertia(fn ($page) => $page->component('Public/EventDetail')->where('event.slug', $event->slug));
    }

    public function test_public_event_search_filters_by_title_or_location(): void
    {
        $match = Event::factory()->create([
            'status' => 'published',
            'title' => 'Workshop Bandung',
            'location_name' => 'Bandung Creative Hub',
        ]);
        Event::factory()->create([
            'status' => 'published',
            'title' => 'Workshop Jakarta',
            'location_name' => 'Jakarta Selatan',
        ]);

        $this->get('/events?search=Bandung')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.search', 'Bandung')
                ->has('events.data', 1)
                ->where('events.data.0.id', $match->id));
    }

    public function test_unpublished_event_page_is_not_found(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);

        $this->get('/events/'.$event->slug)->assertNotFound();
    }

    public function test_guest_can_open_checkout_page_without_authentication(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get('/events/'.$event->slug.'/checkout')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Checkout')->where('event.id', $event->id));
    }

    public function test_refreshing_checkout_page_does_not_create_an_order(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get('/events/'.$event->slug.'/checkout')->assertOk();
        $this->get('/events/'.$event->slug.'/checkout')->assertOk();

        $this->assertDatabaseCount('orders', 0);
    }
}

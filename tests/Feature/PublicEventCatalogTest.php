<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_groups_only_published_events_using_their_actual_schedule(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(12, 0));
        $ongoing = Event::factory()->create(['status' => 'published', 'start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'banner_path' => 'events/ongoing.png']);
        $upcoming = Event::factory()->create(['status' => 'published', 'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHour(), 'banner_path' => 'events/upcoming.png']);
        $ended = Event::factory()->create(['status' => 'published', 'start_at' => now()->subDays(2), 'end_at' => now()->subDay(), 'banner_path' => 'events/ended.png']);
        Event::factory()->create(['status' => 'draft', 'banner_path' => 'events/draft.png']);

        $this->get(route('public.events.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Public/Events')
            ->where('counts', ['all' => 3, 'ongoing' => 1, 'upcoming' => 1])
            ->has('featuredEvents', 2)->where('featuredEvents.0.id', $ongoing->id)
            ->has('ongoingEvents', 1)->where('ongoingEvents.0.id', $ongoing->id)
            ->has('upcomingEvents', 1)->where('upcomingEvents.0.id', $upcoming->id)
            ->has('events.data', 3)
            ->where('events.data.0.schedule_status', 'ongoing')
            ->where('events.data.1.schedule_status', 'upcoming')
            ->where('events.data.2.id', $ended->id)
            ->where('events.data.2.schedule_status', 'ended'));
    }

    public function test_category_and_location_facets_come_from_published_events(): void
    {
        $seminar = Event::factory()->create(['status' => 'published', 'location_name' => 'Bandung Hall']);
        $music = Event::factory()->create(['status' => 'published', 'location_name' => 'Jakarta Hall']);
        $draft = Event::factory()->create(['status' => 'draft', 'location_name' => 'Private Venue']);
        config(['ticket-event.events' => [
            $seminar->slug => ['category' => 'Seminar'],
            $music->slug => ['category' => 'Musik'],
            $draft->slug => ['category' => 'Private Category'],
            'nonexistent-event' => ['category' => 'Unknown Category'],
        ]]);

        $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page
            ->where('categories', [['name' => 'Musik', 'count' => 1], ['name' => 'Seminar', 'count' => 1]])
            ->where('locations', ['Bandung Hall', 'Jakarta Hall']));
        $this->get('/events?category=Seminar&location=Bandung%20Hall')->assertOk()->assertInertia(fn ($page) => $page
            ->where('filters.category', 'Seminar')->where('filters.location', 'Bandung Hall')
            ->has('events.data', 1)->where('events.data.0.id', $seminar->id)
            ->has('featuredEvents', 0)->has('upcomingEvents', 0));
    }

    public function test_schedule_filter_and_counts_are_combined_with_search_and_location(): void
    {
        $ongoing = Event::factory()->create(['status' => 'published', 'title' => 'Workshop ARC', 'location_name' => 'Bandung', 'start_at' => now()->subHour(), 'end_at' => now()->addHour()]);
        Event::factory()->create(['status' => 'published', 'title' => 'Workshop ARC Next', 'location_name' => 'Bandung']);
        Event::factory()->create(['status' => 'published', 'title' => 'Workshop ARC Jakarta', 'location_name' => 'Jakarta']);

        $this->get('/events?search=workshop&location=Bandung&schedule=ongoing')->assertOk()->assertInertia(fn ($page) => $page
            ->where('counts', ['all' => 2, 'ongoing' => 1, 'upcoming' => 1])
            ->has('events.data', 1)->where('events.data.0.id', $ongoing->id)
            ->where('filters.schedule', 'ongoing'));
    }

    public function test_search_matches_titles_or_locations_and_treats_wildcards_literally(): void
    {
        $percent = Event::factory()->create(['status' => 'published', 'title' => '100% Creativity', 'location_name' => 'Bandung']);
        Event::factory()->create(['status' => 'published', 'title' => 'Other Event', 'location_name' => 'Jakarta']);

        foreach (['creativity', 'bandung', '100%', '%'] as $search) {
            $this->get('/events?'.http_build_query(['search' => $search]))->assertOk()->assertInertia(fn ($page) => $page
                ->has('events.data', 1)->where('events.data.0.id', $percent->id));
        }
        $this->get('/events?search=_')->assertOk()->assertInertia(fn ($page) => $page->has('events.data', 0));
    }

    public function test_catalog_paginates_and_keeps_filters_without_duplicating_discovery_sections(): void
    {
        Event::factory()->count(14)->create(['status' => 'published', 'location_name' => 'Bandung', 'title' => 'ARC Workshop']);
        $url = '/events?search=ARC&location=Bandung&schedule=upcoming';

        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->has('events.data', 12)->where('events.total', 14)->where('events.last_page', 2)
            ->where('events.next_page_url', fn ($value) => str_contains($value, 'search=ARC') && str_contains($value, 'location=Bandung') && str_contains($value, 'schedule=upcoming')));
        $this->get($url.'&page=2')->assertOk()->assertInertia(fn ($page) => $page
            ->has('events.data', 2)->has('featuredEvents', 0)->has('ongoingEvents', 0)->has('upcomingEvents', 0));
    }

    public function test_empty_and_invalid_filters_do_not_expose_draft_events_or_create_orders(): void
    {
        Event::factory()->create(['status' => 'draft']);
        $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page
            ->has('events.data', 0)->has('featuredEvents', 0)->has('categories', 0)->has('locations', 0)
            ->where('counts', ['all' => 0, 'ongoing' => 0, 'upcoming' => 0]));
        $this->get('/events?search[]=invalid&category[]=invalid&location[]=invalid&schedule[]=invalid')->assertOk()
            ->assertInertia(fn ($page) => $page->where('filters', ['search' => '', 'category' => '', 'location' => '', 'schedule' => 'all']));
        $this->get('/events?category=Unknown')->assertOk()->assertInertia(fn ($page) => $page->has('events.data', 0));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_card_prices_only_include_active_ticket_types_inside_their_sale_window(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $available = TicketType::factory()->for($event)->create(['price' => '75000.00']);
        TicketType::factory()->for($event)->create(['status' => 'inactive', 'price' => '1000.00']);
        TicketType::factory()->for($event)->create(['sale_start_at' => now()->addDay(), 'price' => '2000.00']);

        $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page
            ->has('events.data.0.ticket_types', 1)
            ->where('events.data.0.ticket_types.0.id', $available->id)
            ->where('events.data.0.ticket_types.0.price', '75000.00'));
    }
}

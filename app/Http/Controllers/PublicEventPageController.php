<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\TicketTypeStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicEventPageController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->with(['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')])
            ->orderBy('start_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Public/Events', [
            'events' => $events->through(fn (Event $event): array => $this->eventData($event)),
        ]);
    }

    public function checkout(Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);
        $event->load(['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')]);

        return Inertia::render('Public/Checkout', [
            'event' => $this->eventData($event),
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);
        $event->load(['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')]);

        return Inertia::render('Public/EventDetail', [
            'event' => $this->eventData($event),
        ]);
    }

    /** @return array<string, mixed> */
    private function eventData(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'description' => $event->description,
            'banner_url' => $event->banner_path ? Storage::disk('public')->url($event->banner_path) : null,
            'start_at' => $event->start_at?->toISOString(),
            'end_at' => $event->end_at?->toISOString(),
            'location_name' => $event->location_name,
            'location_address' => $event->location_address,
            'contact_email' => $event->contact_email,
            'contact_phone' => $event->contact_phone,
            'ticket_types' => $event->ticketTypes->map(fn ($ticketType): array => [
                'id' => $ticketType->id,
                'name' => $ticketType->name,
                'description' => $ticketType->description,
                'price' => (string) $ticketType->price,
                'remaining_quota' => max(0, $ticketType->quota - $ticketType->sold_count),
            ])->values()->all(),
        ];
    }
}

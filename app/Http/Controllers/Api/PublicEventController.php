<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\TicketTypeStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicEventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->with(['ticketTypes' => fn ($query) => $query
                ->where('status', TicketTypeStatus::Active)
                ->availableForSale()
                ->orderBy('price')])
            ->orderBy('start_at')
            ->paginate(12);

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        abort_unless($event->status === EventStatus::Published, 404);

        return new EventResource($event->load(['ticketTypes' => fn ($query) => $query
            ->where('status', TicketTypeStatus::Active)
            ->availableForSale()
            ->orderBy('price')]));
    }
}

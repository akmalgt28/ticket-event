<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrganizerTicketTypeController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse|InertiaResponse
    {
        Gate::authorize('viewAny', TicketType::class);
        $ticketTypes = $event->ticketTypes()->latest('id')->paginate(20)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => TicketTypeResource::collection($ticketTypes)]);
        }

        return Inertia::render('Organizer/TicketTypes', [
            'event' => ['id' => $event->id, 'title' => $event->title],
            'ticketTypes' => $ticketTypes->through(fn (TicketType $ticketType): array => [
                'id' => $ticketType->id, 'name' => $ticketType->name, 'description' => $ticketType->description,
                'price' => (string) $ticketType->price, 'quota' => $ticketType->quota, 'sold_count' => $ticketType->sold_count,
                'remaining_quota' => max(0, $ticketType->quota - $ticketType->sold_count),
                'status' => $ticketType->status?->value,
                'sale_start_at' => $ticketType->sale_start_at?->toISOString(), 'sale_end_at' => $ticketType->sale_end_at?->toISOString(),
            ]),
        ]);
    }

    public function create(Event $event): InertiaResponse
    {
        Gate::authorize('create', TicketType::class);

        return Inertia::render('Organizer/TicketTypeForm', ['event' => ['id' => $event->id, 'title' => $event->title], 'ticketType' => null]);
    }

    public function edit(Event $event, TicketType $ticketType): InertiaResponse
    {
        $this->ensureBelongsToEvent($event, $ticketType);
        Gate::authorize('update', $ticketType);

        return Inertia::render('Organizer/TicketTypeForm', [
            'event' => ['id' => $event->id, 'title' => $event->title],
            'ticketType' => [
                'id' => $ticketType->id, 'name' => $ticketType->name, 'description' => $ticketType->description,
                'price' => (string) $ticketType->price, 'quota' => $ticketType->quota,
                'sale_start_at' => $ticketType->sale_start_at?->format('Y-m-d\TH:i'),
                'sale_end_at' => $ticketType->sale_end_at?->format('Y-m-d\TH:i'), 'status' => $ticketType->status?->value,
            ],
        ]);
    }

    public function store(TicketTypeRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', TicketType::class);
        $ticketType = $event->ticketTypes()->create($request->validated());

        if ($request->header('X-Inertia')) {
            return redirect()->route('organizer.ticket-types.index', $event)->with('success', 'Ticket type berhasil dibuat.');
        }

        return (new TicketTypeResource($ticketType))->response()->setStatusCode(201);
    }

    public function show(Event $event, TicketType $ticketType): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $ticketType);
        Gate::authorize('view', $ticketType);

        return (new TicketTypeResource($ticketType))->response();
    }

    public function update(TicketTypeRequest $request, Event $event, TicketType $ticketType): JsonResponse|RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $ticketType);
        Gate::authorize('update', $ticketType);
        $data = $request->validated();
        unset($data['event_id'], $data['sold_count']);
        $ticketType->update($data);

        if ($request->header('X-Inertia')) {
            return redirect()->route('organizer.ticket-types.index', $event)->with('success', 'Ticket type berhasil diperbarui.');
        }

        return (new TicketTypeResource($ticketType->fresh()))->response();
    }

    public function destroy(Event $event, TicketType $ticketType): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $ticketType);
        Gate::authorize('delete', $ticketType);

        if ($ticketType->orderItems()->exists()) {
            throw ValidationException::withMessages(['ticket_type' => 'Ticket types with orders cannot be deleted.']);
        }

        $ticketType->delete();

        return response()->json(status: 204);
    }

    private function ensureBelongsToEvent(Event $event, TicketType $ticketType): void
    {
        abort_unless($ticketType->event_id === $event->id, 404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrganizerEventController extends Controller
{
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        Gate::authorize('viewAny', Event::class);
        $events = Event::query()->latest('id')->paginate(20)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => EventResource::collection($events)]);
        }

        return Inertia::render('Organizer/Events', [
            'events' => $events->through(fn (Event $event): array => [
                'id' => $event->id, 'title' => $event->title, 'slug' => $event->slug,
                'status' => $event->status?->value, 'start_at' => $event->start_at?->toISOString(),
                'end_at' => $event->end_at?->toISOString(), 'location_name' => $event->location_name,
            ]),
        ]);
    }

    public function create(): InertiaResponse
    {
        Gate::authorize('create', Event::class);

        return Inertia::render('Organizer/EventForm', ['event' => null]);
    }

    public function edit(Event $event): InertiaResponse
    {
        Gate::authorize('update', $event);

        return Inertia::render('Organizer/EventForm', [
            'event' => [
                'id' => $event->id, 'title' => $event->title, 'description' => $event->description,
                'start_at' => $event->start_at?->format('Y-m-d\TH:i'), 'end_at' => $event->end_at?->format('Y-m-d\TH:i'),
                'location_name' => $event->location_name, 'location_address' => $event->location_address,
                'contact_email' => $event->contact_email, 'contact_phone' => $event->contact_phone,
            ],
        ]);
    }

    public function store(EventRequest $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Event::class);
        $data = $request->validated();
        unset($data['banner'], $data['status']);
        $data['status'] = EventStatus::Draft;
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));

        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('events', 'public');
        }

        $event = Event::create($data);

        if ($request->header('X-Inertia')) {
            return redirect()->route('organizer.events.index')->with('success', 'Event berhasil dibuat.');
        }

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function show(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        return (new EventResource($event->load('ticketTypes')))->response();
    }

    public function update(EventRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $event);
        $data = $request->validated();
        unset($data['banner'], $data['status']);

        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('events', 'public');
        }

        $event->update($data);

        if ($request->header('X-Inertia')) {
            return redirect()->route('organizer.events.index')->with('success', 'Event berhasil diperbarui.');
        }

        return (new EventResource($event->fresh()))->response();
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        if ($event->orders()->exists()) {
            throw ValidationException::withMessages(['event' => 'Events with orders cannot be deleted.']);
        }

        $event->delete();

        return response()->json(status: 204);
    }

    public function publish(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        Gate::authorize('publish', $event);

        if (! $event->ticketTypes()->exists()) {
            throw ValidationException::withMessages(['event' => 'An event needs at least one ticket type before publishing.']);
        }
        if (! $event->status->canTransitionTo(EventStatus::Published)) {
            throw ValidationException::withMessages(['status' => 'This event cannot be published from its current status.']);
        }

        $event->update(['status' => EventStatus::Published, 'published_at' => now()]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', "Event {$event->title} berhasil dipublish.");
        }

        return (new EventResource($event->fresh()))->response();
    }

    public function close(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        Gate::authorize('close', $event);

        if (! $event->status->canTransitionTo(EventStatus::Closed)) {
            throw ValidationException::withMessages(['status' => 'This event cannot be closed from its current status.']);
        }

        $event->update(['status' => EventStatus::Closed]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', "Event {$event->title} berhasil ditutup.");
        }

        return (new EventResource($event->fresh()))->response();
    }
}

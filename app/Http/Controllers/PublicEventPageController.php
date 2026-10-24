<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\TicketTypeStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Event;
use App\Services\GuestCheckoutService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicEventPageController extends Controller
{
    public function index(Request $request): Response
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));

                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->with(['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')])
            ->orderBy('start_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Public/Events', [
            'events' => $events->through(fn (Event $event): array => $this->eventData($event)),
            'filters' => ['search' => (string) $request->query('search', '')],
        ]);
    }

    public function dataDiri(Request $request, Event $event): Response|RedirectResponse
    {
        abort_unless($event->status === EventStatus::Published, 404);
        $event->load(['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')]);

        $validator = Validator::make($request->query(), [
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        if ($validator->fails()) {
            return to_route('public.events.checkout', $event->slug)
                ->withErrors(['qty' => 'Pilih tiket yang valid, maksimal 20 tiket per jenis.']);
        }

        $items = [];
        $ticketTypes = $event->ticketTypes->keyBy('id');

        foreach ($validator->validated()['qty'] as $ticketTypeId => $quantity) {
            if ((int) $quantity === 0) {
                continue;
            }

            $ticketType = $ticketTypes->get($ticketTypeId);

            if (! $ticketType || (int) $quantity > $ticketType->quota - $ticketType->sold_count) {
                return to_route('public.events.checkout', $event->slug)
                    ->withErrors(['qty' => 'Tiket yang dipilih sudah tidak tersedia. Silakan pilih kembali.']);
            }

            $items[] = ['ticket_type_id' => $ticketType->id, 'quantity' => (int) $quantity];
        }

        if ($items === []) {
            return to_route('public.events.checkout', $event->slug)
                ->withErrors(['qty' => 'Pilih minimal satu tiket terlebih dahulu.']);
        }

        return Inertia::render('Public/DataDiri', [
            'event' => $this->eventData($event),
            'items' => $items,
        ]);
    }

    public function storeCheckout(CheckoutRequest $request, Event $event, GuestCheckoutService $checkout, PaymentService $payments): RedirectResponse
    {
        $data = $request->validated();

        if ((int) $data['event_id'] !== $event->id) {
            throw ValidationException::withMessages(['event_id' => 'Event tidak sesuai dengan pilihan tiket.']);
        }

        $order = DB::transaction(function () use ($event, $data, $checkout, $payments) {
            $order = $checkout->create($event, $data);
            $payments->selectManualTransfer($order);

            return $order;
        });

        $request->session()->put('public_checkout', [
            'order_number' => $order->order_number,
            'customer_email' => $order->customer_email,
        ]);

        return to_route('public.orders.payment');
    }

    public function checkout(Request $request, Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);
        $event->load(['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')]);
        $selection = Validator::make($request->query(), [
            'qty' => ['sometimes', 'array'],
            'qty.*' => ['integer', 'min:0', 'max:20'],
        ]);

        return Inertia::render('Public/Checkout', [
            'event' => $this->eventData($event),
            'quantities' => $selection->passes() ? ($selection->validated()['qty'] ?? []) : [],
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
        $details = config('ticket-event.events', [])[$event->slug] ?? [];

        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'description' => $event->description,
            'category' => $event->getAttribute('category') ?? $details['category'] ?? null,
            'organizer_name' => $event->getAttribute('organizer_name') ?? $details['organizer_name'] ?? null,
            'terms' => $event->getAttribute('terms') ?? $details['terms'] ?? null,
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

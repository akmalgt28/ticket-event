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
        $now = now();
        $stringQuery = fn (string $key, int $length = 255): string => is_string($request->query($key))
            ? mb_substr(trim($request->query($key)), 0, $length)
            : '';
        $filters = [
            'search' => $stringQuery('search', 100),
            'category' => $stringQuery('category', 100),
            'location' => $stringQuery('location'),
            'schedule' => in_array($request->query('schedule'), ['ongoing', 'upcoming'], true) ? $request->query('schedule') : 'all',
        ];

        $published = Event::query()->where('status', EventStatus::Published);
        $configuredCategories = collect(config('ticket-event.events', []))
            ->filter(fn ($details): bool => is_array($details) && is_string($details['category'] ?? null) && trim($details['category']) !== '')
            ->map(fn ($details): string => trim($details['category']));
        $publishedSlugs = (clone $published)->whereIn('slug', $configuredCategories->keys())->pluck('slug');
        $categoriesBySlug = $configuredCategories->only($publishedSlugs->all());
        $ticketTypes = ['ticketTypes' => fn ($query) => $query->where('status', TicketTypeStatus::Active)->availableForSale()->orderBy('price')];

        $catalog = (clone $published)
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['search'])).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereRaw("LOWER(title) LIKE ? ESCAPE '!'", [$search])
                        ->orWhereRaw("LOWER(location_name) LIKE ? ESCAPE '!'", [$search]);
                });
            })
            ->when($filters['category'] !== '', fn ($query) => $query->whereIn('slug', $categoriesBySlug->filter(fn ($category) => $category === $filters['category'])->keys()))
            ->when($filters['location'] !== '', fn ($query) => $query->where('location_name', $filters['location']));

        $ongoing = fn ($query) => $query->where('start_at', '<=', $now)->where('end_at', '>=', $now);
        $upcoming = fn ($query) => $query->where('start_at', '>', $now);
        $counts = [
            'all' => (clone $catalog)->count(),
            'ongoing' => $ongoing(clone $catalog)->count(),
            'upcoming' => $upcoming(clone $catalog)->count(),
        ];
        $toCard = fn (Event $event): array => [
            ...$this->eventData($event),
            'schedule_status' => $event->start_at?->gt($now) ? 'upcoming'
                : ($event->start_at && $event->end_at?->gte($now) ? 'ongoing' : 'ended'),
        ];
        $selection = clone $catalog;
        if ($filters['schedule'] === 'ongoing') {
            $ongoing($selection);
        } elseif ($filters['schedule'] === 'upcoming') {
            $upcoming($selection);
        }
        $events = $selection->with($ticketTypes)
            ->orderByRaw('CASE WHEN end_at < ? THEN 1 ELSE 0 END', [$now])
            ->orderBy('start_at')->orderBy('id')->paginate(12)
            ->appends(array_filter($filters, fn ($value) => $value !== '' && $value !== 'all'));
        $discover = $filters['search'] === '' && $filters['category'] === '' && $filters['location'] === '' && $filters['schedule'] === 'all' && $events->currentPage() === 1;

        return Inertia::render('Public/Events', [
            'events' => $events->through($toCard),
            'filters' => $filters,
            'categories' => $categoriesBySlug->countBy()->sortKeys()->map(fn ($count, $name) => ['name' => $name, 'count' => $count])->values(),
            'locations' => (clone $published)->whereNotNull('location_name')->where('location_name', '!=', '')->distinct()->orderBy('location_name')->pluck('location_name'),
            'counts' => $counts,
            'featuredEvents' => $discover ? (clone $published)->where('end_at', '>=', $now)
                ->whereNotNull('banner_path')->where('banner_path', '!=', '')
                ->with($ticketTypes)->orderBy('start_at')->orderBy('id')->limit(4)->get()->map($toCard) : [],
            'ongoingEvents' => $discover ? $ongoing(clone $published)->with($ticketTypes)->orderBy('start_at')->orderBy('id')->limit(4)->get()->map($toCard) : [],
            'upcomingEvents' => $discover ? $upcoming(clone $published)->with($ticketTypes)->orderBy('start_at')->orderBy('id')->limit(4)->get()->map($toCard) : [],
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

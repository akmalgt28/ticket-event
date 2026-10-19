<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Enums\TicketTypeStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', Order::class);

        $paidOrders = Order::query()->where('payment_status', PaymentStatus::Paid->value);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'orders' => Order::query()->count(),
                'paid_orders' => (clone $paidOrders)->count(),
                'tickets_sold' => Ticket::query()
                    ->whereIn('status', [TicketStatus::Issued->value, TicketStatus::Used->value])
                    ->count(),
                'tickets_remaining' => (int) TicketType::query()
                    ->where('status', TicketTypeStatus::Active->value)
                    ->selectRaw('COALESCE(SUM(quota - sold_count), 0) AS remaining')
                    ->value('remaining'),
                'revenue' => bcadd((string) (clone $paidOrders)->sum('total_amount'), '0', 2),
            ],
            'recentEvents' => Event::query()
                ->where('status', EventStatus::Published->value)
                ->latest('start_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'start_at', 'location_name'])
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'start_at' => $event->start_at?->toISOString(),
                    'location_name' => $event->location_name,
                ])
                ->values(),
        ]);
    }
}

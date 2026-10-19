<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OrganizerCheckInController extends Controller
{
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        Gate::authorize('viewReports');

        $checkIns = Ticket::query()
            ->whereNotNull('checked_in_at')
            ->with([
                'attendee:id,name,email',
                'order:id,order_number,event_id',
                'order.event:id,title',
                'checkedInBy:id,name',
            ])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('ticket_number', 'like', "%{$search}%")
                        ->orWhereHas('attendee', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('order', fn ($query) => $query->where('order_number', 'like', "%{$search}%"));
                });
            })
            ->latest('checked_in_at')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => $checkIns]);
        }

        return Inertia::render('Organizer/CheckIns', [
            'checkIns' => $checkIns->through(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'attendee' => $ticket->attendee?->name,
                'order_number' => $ticket->order?->order_number,
                'event' => $ticket->order?->event?->title,
                'checked_in_at' => $ticket->checked_in_at?->toISOString(),
                'checked_in_by' => $ticket->checkedInBy?->name,
            ]),
            'filters' => ['search' => (string) $request->query('search', '')],
        ]);
    }
}

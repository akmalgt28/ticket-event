<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendeeResource;
use App\Models\Attendee;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizerParticipantController extends Controller
{
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        Gate::authorize('viewAny', Order::class);

        $participants = $this->participantsQuery($request)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => AttendeeResource::collection($participants),
            ]);
        }

        return Inertia::render('Organizer/Participants', [
            'participants' => $participants->through(fn (Attendee $participant): array => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'order_number' => $participant->order?->order_number,
            ]),
            'filters' => [
                'search' => (string) $request->query('search', ''),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Order::class);

        return response()->streamDownload(function () use ($request): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['id', 'name', 'email', 'phone', 'order_number']);

            foreach ($this->participantsQuery($request)->lazy(500) as $participant) {
                fputcsv($output, [
                    $participant->id,
                    $participant->name,
                    $participant->email,
                    $participant->phone,
                    $participant->order?->order_number,
                ]);
            }

            fclose($output);
        }, 'participants.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function participantsQuery(Request $request): Builder
    {
        return Attendee::query()
            ->with('order:id,event_id,order_number')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($query) use ($search): void {
                            $query->where('order_number', 'like', "%{$search}%");
                        });
                });
            });
    }
}

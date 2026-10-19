<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OrganizerOrderController extends Controller
{
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        Gate::authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with('event:id,title')
            ->when(! $request->expectsJson(), fn ($query) => $query->with('payments:id,order_id,status,amount,paid_at'))
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = OrderStatus::tryFrom((string) $request->query('status'));

                if ($status !== null) {
                    $query->where('order_status', $status->value);
                }
            })
            ->when($request->filled('payment_status'), function ($query) use ($request): void {
                $status = PaymentStatus::tryFrom((string) $request->query('payment_status'));

                if ($status !== null) {
                    $query->where('payment_status', $status->value);
                }
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));

                $query->where(function ($query) use ($search): void {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => OrderResource::collection($orders),
            ]);
        }

        return Inertia::render('Organizer/Orders', [
            'orders' => $orders->through(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'total_amount' => (string) $order->total_amount,
                'order_status' => $order->order_status?->value,
                'payment_status' => $order->payment_status?->value,
                'event' => $order->event?->title,
                'payment' => $order->payments->first() ? [
                    'status' => $order->payments->first()->status?->value,
                    'amount' => (string) $order->payments->first()->amount,
                ] : null,
            ]),
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'status' => (string) $request->query('status', ''),
                'payment_status' => (string) $request->query('payment_status', ''),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return (new OrderResource($order->load([
            'event:id,title,slug,start_at,end_at,location_name,location_address',
            'items.ticketType:id,name',
            'attendees',
            'payments',
            'tickets',
        ])))->response();
    }
}

<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestCheckoutService
{
    public function __construct(
        private OrderCalculator $orderCalculator,
        private QuotaReservationService $quotaReservation,
    ) {}

    public function create(Event $event, array $data): Order
    {

        return DB::transaction(function () use ($event, $data): Order {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->status !== EventStatus::Published) {
                throw ValidationException::withMessages([
                    'event_id' => 'This event is not accepting orders.',
                ]);
            }

            $calculation = $this->orderCalculator->calculate($lockedEvent, $data['items']);
            $this->quotaReservation->reserve($lockedEvent, $data['items']);

            $order = Order::create([
                'event_id' => $lockedEvent->id,
                'order_number' => 'ORD-'.Str::upper(Str::random(16)),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'total_amount' => $calculation['total_amount'],
                'order_status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'expires_at' => now()->addMinutes(30),
            ]);

            foreach ($calculation['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'ticket_type_id' => $item['ticket_type_id'],
                    'ticket_name_snapshot' => $item['ticket_name_snapshot'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            foreach ($data['attendees'] as $attendeeData) {
                Attendee::create([
                    'order_id' => $order->id,
                    'name' => $attendeeData['name'],
                    'email' => $attendeeData['email'],
                    'phone' => $attendeeData['phone'],
                    'metadata' => $attendeeData['metadata'] ?? null,
                ]);
            }

            return $order->load(['items', 'attendees']);
        });
    }
}

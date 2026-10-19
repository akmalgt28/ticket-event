<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;

class OrderExpirationService
{
    public function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->order_status !== OrderStatus::Pending || ! $lockedOrder->expires_at?->isPast()) {
                return false;
            }

            $items = $lockedOrder->items()->orderBy('ticket_type_id')->get();

            foreach ($items as $item) {
                $ticketType = TicketType::query()->whereKey($item->ticket_type_id)->lockForUpdate()->firstOrFail();
                $ticketType->update([
                    'sold_count' => max(0, $ticketType->sold_count - $item->quantity),
                ]);
            }

            $lockedOrder->update([
                'order_status' => OrderStatus::Expired,
                'payment_status' => PaymentStatus::Expired,
            ]);
            $lockedOrder->payments()->where('status', PaymentStatus::Pending->value)->update([
                'status' => PaymentStatus::Expired,
            ]);

            return true;
        });
    }
}

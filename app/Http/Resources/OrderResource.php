<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status?->value,
            'payment_status' => $this->payment_status?->value,
            'expires_at' => $this->expires_at?->toISOString(),
            'event' => $this->whenLoaded('event', fn (): array => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'slug' => $this->event->slug,
                'start_at' => $this->event->start_at?->toISOString(),
                'end_at' => $this->event->end_at?->toISOString(),
                'location' => [
                    'name' => $this->event->location_name,
                    'address' => $this->event->location_address,
                ],
            ]),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'attendees' => AttendeeResource::collection($this->whenLoaded('attendees')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}

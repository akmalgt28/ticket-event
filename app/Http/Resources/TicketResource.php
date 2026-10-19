<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'attendee_id' => $this->attendee_id,
            'status' => $this->status?->value,
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'checked_in_by' => $this->checked_in_by,
        ];
    }
}

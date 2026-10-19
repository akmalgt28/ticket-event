<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_type_id' => $this->ticket_type_id,
            'ticket_name' => $this->ticket_name_snapshot,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
        ];
    }
}

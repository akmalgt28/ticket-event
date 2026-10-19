<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'order' => $this->whenLoaded('order', fn (): array => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]),
        ];
    }
}

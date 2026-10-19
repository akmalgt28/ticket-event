<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status?->value,
            'description' => $this->description,
            'banner_path' => $this->banner_path,
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'location' => [
                'name' => $this->location_name,
                'address' => $this->location_address,
            ],
            'contact' => [
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ],
            'ticket_types' => TicketTypeResource::collection($this->whenLoaded('ticketTypes')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'quota' => $this->quota,
            'sold_count' => $this->sold_count,
            'remaining_quota' => max(0, $this->quota - $this->sold_count),
            'sale_start_at' => $this->sale_start_at?->toISOString(),
            'sale_end_at' => $this->sale_end_at?->toISOString(),
        ];
    }
}

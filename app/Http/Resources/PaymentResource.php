<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'method' => $this->method,
            'amount' => $this->amount,
            'status' => $this->status?->value,
            'provider_transaction_id' => $this->provider_transaction_id,
            'paid_at' => $this->paid_at?->toISOString(),
        ];
    }
}

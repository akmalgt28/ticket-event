<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'proof_url' => $this->proof_path ? Storage::disk('public')->url($this->proof_path) : null,
            'proof_uploaded_at' => $this->proof_uploaded_at?->toISOString(),
        ];
    }
}

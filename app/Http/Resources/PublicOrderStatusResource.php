<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicOrderStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->payments->first();

        return [
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status?->value,
            'payment_status' => $this->payment_status?->value,
            'expires_at' => $this->expires_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'payment' => $payment ? [
                'method' => $payment->method,
                'amount' => $payment->amount,
                'status' => $payment->status?->value,
                'paid_at' => $payment->paid_at?->toISOString(),
            ] : null,
        ];
    }
}

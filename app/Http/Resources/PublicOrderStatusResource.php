<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PublicOrderStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->payments->first();

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status?->value,
            'payment_status' => $this->payment_status?->value,
            'expires_at' => $this->expires_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'event' => [
                'title' => $this->event->title,
                'slug' => $this->event->slug,
                'banner_url' => $this->event->banner_path ? Storage::disk('public')->url($this->event->banner_path) : null,
                'contact_email' => $this->event->contact_email,
                'contact_phone' => $this->event->contact_phone,
            ],
            'payment_options' => [
                'methods' => [['id' => 'bank_transfer', 'label' => 'Bank transfer']],
                'bank_accounts' => collect(config('ticket-event.bank_transfer.accounts', []))
                    ->filter(fn ($account) => is_array($account)
                        && filled($account['bank_name'] ?? null)
                        && filled($account['account_number'] ?? null)
                        && filled($account['account_holder'] ?? null))
                    ->map(fn ($account) => [
                        'bank_name' => (string) $account['bank_name'],
                        'account_number' => (string) $account['account_number'],
                        'account_holder' => (string) $account['account_holder'],
                    ])->values()->all(),
            ],
            'payment' => $payment ? [
                'method' => $payment->method,
                'amount' => $payment->amount,
                'status' => $payment->status?->value,
                'paid_at' => $payment->paid_at?->toISOString(),
                'proof_uploaded_at' => $payment->proof_uploaded_at?->toISOString(),
            ] : null,
            'tickets' => $this->order_status?->value === 'paid'
                ? $this->tickets->map(fn ($ticket): array => [
                    'ticket_number' => $ticket->ticket_number,
                    'attendee_name' => $ticket->attendee?->name ?? 'Peserta',
                    'status' => $ticket->status?->value,
                    'checked_in_at' => $ticket->checked_in_at?->toISOString(),
                    'qr_svg' => $ticket->qr_token
                        ? (string) QrCode::format('svg')->size(240)->margin(1)->generate($ticket->qr_token)
                        : null,
                ])->values()->all()
                : [],
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInService
{
    public function checkIn(?string $token, User $user, ?string $ticketNumber = null): Ticket
    {
        return DB::transaction(function () use ($token, $user, $ticketNumber): Ticket {
            $ticket = Ticket::query()
                ->when($ticketNumber, fn ($query) => $query->where('ticket_number', $ticketNumber), fn ($query) => $query->where('qr_token_hash', hash('sha256', (string) $token)))
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw ValidationException::withMessages([
                    'token' => 'Ticket token is invalid.',
                ]);
            }

            if ($ticket->status !== TicketStatus::Issued || $ticket->checked_in_at) {
                throw ValidationException::withMessages([
                    'token' => 'This ticket has already been used or cancelled.',
                ]);
            }

            if ($ticket->order()->where('order_status', '!=', OrderStatus::Paid->value)->exists()) {
                throw ValidationException::withMessages([
                    'token' => 'This ticket is not eligible for check-in.',
                ]);
            }

            $ticket->update([
                'status' => TicketStatus::Used,
                'checked_in_at' => now(),
                'checked_in_by' => $user->id,
            ]);

            return $ticket->fresh(['attendee:id,name', 'order:id,event_id', 'order.event:id,title']);
        });
    }
}

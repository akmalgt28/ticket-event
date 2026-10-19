<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInService
{
    public function checkIn(string $token, User $user): Ticket
    {
        return DB::transaction(function () use ($token, $user): Ticket {
            $ticket = Ticket::query()
                ->where('qr_token_hash', hash('sha256', $token))
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

            $ticket->update([
                'status' => TicketStatus::Used,
                'checked_in_at' => now(),
                'checked_in_by' => $user->id,
            ]);

            return $ticket->fresh();
        });
    }
}

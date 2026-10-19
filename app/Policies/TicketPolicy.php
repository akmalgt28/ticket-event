<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.manage');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.manage');
    }

    public function checkIn(User $user, Ticket $ticket): bool
    {
        return $user->can('checkin.manage');
    }
}

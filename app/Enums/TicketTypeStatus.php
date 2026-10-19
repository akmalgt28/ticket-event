<?php

namespace App\Enums;

enum TicketTypeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function canTransitionTo(self $next): bool
    {
        return $this !== $next;
    }
}

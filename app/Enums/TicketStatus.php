<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Issued = 'issued';
    case Used = 'used';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Issued => in_array($next, [self::Used, self::Cancelled], true),
            self::Used, self::Cancelled => false,
        };
    }
}

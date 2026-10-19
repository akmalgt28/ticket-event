<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Published, self::Cancelled], true),
            self::Published => in_array($next, [self::Closed, self::Cancelled], true),
            self::Closed, self::Cancelled => false,
        };
    }
}

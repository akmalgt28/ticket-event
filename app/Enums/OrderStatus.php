<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Paid, self::Expired, self::Cancelled], true),
            self::Paid => $next === self::Refunded,
            self::Expired, self::Cancelled, self::Refunded => false,
        };
    }
}

<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Paid, self::Failed, self::Expired], true),
            self::Paid => $next === self::Refunded,
            self::Failed, self::Expired, self::Refunded => false,
        };
    }
}

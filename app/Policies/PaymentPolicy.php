<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.verify');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify');
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify');
    }
}

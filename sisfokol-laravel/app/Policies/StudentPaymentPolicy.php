<?php

namespace App\Policies;

use App\Models\StudentPayment;
use App\Models\User;

class StudentPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('finance.student-payment.*') || $user->hasPermissionTo('finance.student-payment.view');
    }

    public function view(User $user, StudentPayment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('finance.student-payment.*');
    }

    public function update(User $user, StudentPayment $payment): bool
    {
        return $user->hasPermissionTo('finance.student-payment.*');
    }

    public function delete(User $user, StudentPayment $payment): bool
    {
        return $user->hasPermissionTo('finance.student-payment.*');
    }
}

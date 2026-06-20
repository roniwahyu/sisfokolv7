<?php

namespace App\Policies;

use App\Models\StudentViolation;
use App\Models\User;

class StudentViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('violation.*') || $user->hasPermissionTo('violation.view');
    }

    public function view(User $user, StudentViolation $violation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('violation.*');
    }

    public function update(User $user, StudentViolation $violation): bool
    {
        return $user->hasPermissionTo('violation.*');
    }

    public function delete(User $user, StudentViolation $violation): bool
    {
        return $user->hasPermissionTo('violation.*');
    }
}

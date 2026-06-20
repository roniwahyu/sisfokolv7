<?php

namespace App\Policies;

use App\Models\Absence;
use App\Models\User;

class AbsencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('absence.*') || $user->hasPermissionTo('absence.view');
    }

    public function view(User $user, Absence $absence): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('absence.*');
    }

    public function update(User $user, Absence $absence): bool
    {
        return $user->hasPermissionTo('absence.*');
    }

    public function delete(User $user, Absence $absence): bool
    {
        return $user->hasPermissionTo('absence.*');
    }
}

<?php

namespace App\Policies;

use App\Models\StudentSaving;
use App\Models\User;

class StudentSavingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('finance.student-saving.*') || $user->hasPermissionTo('finance.student-saving.view');
    }

    public function view(User $user, StudentSaving $saving): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('finance.student-saving.*');
    }

    public function update(User $user, StudentSaving $saving): bool
    {
        return $user->hasPermissionTo('finance.student-saving.*');
    }

    public function delete(User $user, StudentSaving $saving): bool
    {
        return $user->hasPermissionTo('finance.student-saving.*');
    }
}

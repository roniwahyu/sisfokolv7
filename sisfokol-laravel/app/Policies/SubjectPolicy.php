<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('master.subject.*') || $user->hasPermissionTo('academic.curriculum.*');
    }

    public function view(User $user, Subject $subject): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('master.subject.*');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasPermissionTo('master.subject.*');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasPermissionTo('master.subject.*');
    }
}

<?php

namespace App\Policies;

use App\Models\Permit;
use App\Models\User;

class PermitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('permit.*') || $user->hasPermissionTo('permit.view');
    }

    public function view(User $user, Permit $permit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('permit.*');
    }

    public function update(User $user, Permit $permit): bool
    {
        return $user->hasPermissionTo('permit.*');
    }

    public function delete(User $user, Permit $permit): bool
    {
        return $user->hasPermissionTo('permit.*');
    }

    public function approve(User $user, Permit $permit): bool
    {
        return $user->hasPermissionTo('permit.*');
    }
}

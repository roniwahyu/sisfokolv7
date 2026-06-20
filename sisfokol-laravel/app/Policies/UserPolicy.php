<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('user.*');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.*');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('user.*');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.*');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.*');
    }
}

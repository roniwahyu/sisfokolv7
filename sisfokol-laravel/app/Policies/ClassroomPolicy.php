<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('master.classroom.*') || $user->hasPermissionTo('master.classroom.view');
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('master.classroom.*') || $user->hasPermissionTo('master.classroom.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('master.classroom.*');
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('master.classroom.*');
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('master.classroom.*');
    }
}

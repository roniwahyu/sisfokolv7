<?php

namespace App\Modules\Presence\Policies;

use App\Models\Attendance;
use App\Models\User;

class PresensiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('presence.view') || $user->can('presence.*');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->can('presence.view') && $user->tenant_id === $attendance->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('presence.*');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->can('presence.*') && $user->tenant_id === $attendance->tenant_id;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->can('presence.*') && $user->tenant_id === $attendance->tenant_id;
    }
}

<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Jadwal;

class JadwalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academic.schedule.*') || $user->can('academic.schedule.view');
    }

    public function view(User $user, Jadwal $jadwal): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('academic.schedule.*') || $user->can('academic.schedule.view'))
            && $user->tenant_id === $jadwal->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('academic.schedule.*');
    }

    public function update(User $user, Jadwal $jadwal): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('academic.schedule.*') && $user->tenant_id === $jadwal->tenant_id;
    }

    public function delete(User $user, Jadwal $jadwal): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('academic.schedule.*') && $user->tenant_id === $jadwal->tenant_id;
    }
}

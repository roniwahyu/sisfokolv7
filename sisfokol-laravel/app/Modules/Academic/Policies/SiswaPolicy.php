<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;

class SiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('student.*') || $user->can('student.view');
    }

    public function view(User $user, Siswa $siswa): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('student.*') || $user->can('student.view'))
            && $user->tenant_id === $siswa->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('student.*');
    }

    public function update(User $user, Siswa $siswa): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('student.*') && $user->tenant_id === $siswa->tenant_id;
    }

    public function delete(User $user, Siswa $siswa): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('student.*') && $user->tenant_id === $siswa->tenant_id;
    }
}

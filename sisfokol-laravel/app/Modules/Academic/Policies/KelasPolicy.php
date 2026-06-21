<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Kelas;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.classroom.*') || $user->can('student.view') || $user->can('employee.view');
    }

    public function view(User $user, Kelas $kelas): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('master.classroom.*') || $user->can('student.view') || $user->can('employee.view'))
            && $user->tenant_id === $kelas->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('master.classroom.*');
    }

    public function update(User $user, Kelas $kelas): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('master.classroom.*') && $user->tenant_id === $kelas->tenant_id;
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('master.classroom.*') && $user->tenant_id === $kelas->tenant_id;
    }
}

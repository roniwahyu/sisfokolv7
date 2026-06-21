<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\TabunganSiswa;

class TabunganPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.*') 
            || $user->can('finance.student-saving.*');
    }

    public function view(User $user, TabunganSiswa $tabungan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole('student')) {
            $isOwn = $user->userable_type === 'App\Modules\Academic\Models\Siswa' && $user->userable_id === $tabungan->siswa_id;
            return $isOwn && $user->tenant_id === $tabungan->tenant_id;
        }

        return ($user->can('finance.*') || $user->can('finance.student-saving.*'))
            && $user->tenant_id === $tabungan->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('finance.*') || $user->can('finance.student-saving.*');
    }

    public function update(User $user, TabunganSiswa $tabungan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('finance.*') || $user->can('finance.student-saving.*'))
            && $user->tenant_id === $tabungan->tenant_id;
    }
}

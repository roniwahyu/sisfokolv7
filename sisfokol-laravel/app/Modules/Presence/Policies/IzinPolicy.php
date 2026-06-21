<?php

namespace App\Modules\Presence\Policies;

use App\Models\Permit;
use App\Models\User;

class IzinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('permit.*') || $user->can('absence.*');
    }

    public function view(User $user, Permit $permit): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->can('permit.*') && $user->tenant_id === $permit->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('permit.*');
    }

    public function update(User $user, Permit $permit): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->can('permit.*') && $user->tenant_id === $permit->tenant_id;
    }

    public function delete(User $user, Permit $permit): bool
    {
        if ($user->isSuperAdmin()) return true;

        return $user->can('permit.*') && $user->tenant_id === $permit->tenant_id;
    }

    /**
     * Only picket-officer and counselor roles can approve/reject permits.
     */
    public function approve(User $user, Permit $permit): bool
    {
        if ($user->isSuperAdmin()) return true;

        return ($user->hasAnyRole(['picket-officer', 'counselor']))
            && $user->tenant_id === $permit->tenant_id
            && $permit->status === 'pending';
    }
}

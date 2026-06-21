<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\ItemPembayaran;

class ItemPembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.*') || $user->can('finance.payment-item.*');
    }

    public function view(User $user, ItemPembayaran $item): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('finance.*') || $user->can('finance.payment-item.*'))
            && $user->tenant_id === $item->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('finance.*') || $user->can('finance.payment-item.*');
    }

    public function update(User $user, ItemPembayaran $item): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('finance.*') || $user->can('finance.payment-item.*'))
            && $user->tenant_id === $item->tenant_id;
    }

    public function delete(User $user, ItemPembayaran $item): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return ($user->can('finance.*') || $user->can('finance.payment-item.*'))
            && $user->tenant_id === $item->tenant_id;
    }
}

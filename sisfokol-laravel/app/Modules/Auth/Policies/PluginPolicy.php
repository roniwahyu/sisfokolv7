<?php

namespace App\Modules\Auth\Policies;

use App\Models\User;

class PluginPolicy
{
    public function activate(User $user): bool
    {
        return $user->can('plugin.activate');
    }
}

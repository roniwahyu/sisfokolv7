<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('academic.schedule.*') || $user->hasPermissionTo('academic.schedule.view');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('academic.schedule.*');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->hasPermissionTo('academic.schedule.*');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->hasPermissionTo('academic.schedule.*');
    }
}

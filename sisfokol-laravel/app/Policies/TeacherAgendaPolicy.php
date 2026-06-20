<?php

namespace App\Policies;

use App\Models\TeacherAgenda;
use App\Models\User;

class TeacherAgendaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('academic.teacher-agenda.*') || $user->hasPermissionTo('academic.teacher-agenda.view');
    }

    public function view(User $user, TeacherAgenda $agenda): bool
    {
        return $user->hasPermissionTo('academic.teacher-agenda.*') || $user->id === $agenda->employee?->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('academic.teacher-agenda.*') || $user->hasRole('teacher');
    }

    public function update(User $user, TeacherAgenda $agenda): bool
    {
        return $user->hasPermissionTo('academic.teacher-agenda.*') || $user->id === $agenda->employee?->user_id;
    }

    public function delete(User $user, TeacherAgenda $agenda): bool
    {
        return $user->hasPermissionTo('academic.teacher-agenda.*') || $user->id === $agenda->employee?->user_id;
    }
}

<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Employee;
use App\Models\LoginLog;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    public function getAdminStats(): array
    {
        $today = Carbon::today();
        $last14Days = collect(range(0, 14))->map(fn ($i) => $today->copy()->subDays($i)->format('Y-m-d'));

        $loginData = $last14Days->map(function ($date) {
            return LoginLog::whereDate('logged_in_at', $date)->count();
        });

        return [
            'total_employees' => Employee::count(),
            'total_students' => Student::count(),
            'total_classrooms' => Classroom::count(),
            'total_subjects' => Subject::count(),
            'total_users' => User::count(),
            'last_logins' => LoginLog::with('user')->latest()->limit(10)->get(),
            'login_chart_labels' => $last14Days->map(fn ($d) => Carbon::parse($d)->format('d/m'))->reverse()->values(),
            'login_chart_data' => $loginData->reverse()->values(),
            'last_login_at' => optional(LoginLog::latest()->first())->logged_in_at,
        ];
    }

    public function getTeacherStats(User $user): array
    {
        $employee = $user->userable;

        return [
            'name' => $employee?->name ?? $user->display_name,
            'total_subjects' => $employee?->subjects()->count() ?? 0,
            'total_schedules' => $employee?->schedules()->count() ?? 0,
            'total_classrooms' => $employee?->schedules()->distinct('classroom_id')->count() ?? 0,
            'today_schedules' => $employee?->schedules()->where('day_id', Carbon::now()->dayOfWeek)->get() ?? collect(),
        ];
    }

    public function getStudentStats(User $user): array
    {
        $student = $user->userable;

        return [
            'name' => $student?->name ?? $user->display_name,
            'classroom' => $student?->classroom?->name ?? '-',
            'academic_year' => $student?->academicYear?->name ?? '-',
        ];
    }

    public function getHomeroomStats(User $user): array
    {
        $classroom = $user->userable?->homeroomClass;

        return [
            'name' => $user->display_name,
            'classroom' => $classroom?->name ?? '-',
            'total_students' => $classroom?->students()->count() ?? 0,
        ];
    }

    public function getFinanceStats(): array
    {
        return [
            'total_students' => Student::count(),
            'total_active_users' => User::where('is_active', true)->count(),
        ];
    }

    public function getCounselorStats(): array
    {
        return [
            'total_students' => Student::count(),
        ];
    }

    public function getPicketStats(): array
    {
        return [
            'total_students' => Student::count(),
            'total_employees' => Employee::count(),
        ];
    }

    public function getInventoryStats(): array
    {
        return [
            'total_employees' => Employee::count(),
        ];
    }
}

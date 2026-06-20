<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleService
{
    public function getTodaySchedules(?int $employeeId = null, ?int $classroomId = null)
    {
        $dayNumber = Carbon::now()->dayOfWeek;

        $query = Schedule::with(['classroom', 'subject', 'teacher', 'room', 'timeSlot'])
            ->where('day_id', $dayNumber);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        return $query->orderBy('time_slot_id')->get();
    }

    public function getClassroomSchedule(int $classroomId, ?int $academicYearId = null)
    {
        $query = Schedule::with(['subject', 'teacher', 'room', 'timeSlot', 'day'])
            ->where('classroom_id', $classroomId);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->orderBy('day_id')->orderBy('time_slot_id')->get();
    }

    public function checkConflict(int $academicYearId, int $classroomId, int $dayId, int $timeSlotId, ?int $excludeId = null): bool
    {
        $query = Schedule::where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->where('day_id', $dayId)
            ->where('time_slot_id', $timeSlotId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

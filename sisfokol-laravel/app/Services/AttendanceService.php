<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceTime;
use Carbon\Carbon;

class AttendanceService
{
    public function getTodayAttendanceCount(string $type = 'in'): int
    {
        return Attendance::where('date', Carbon::today()->toDateString())
            ->where('type', $type)
            ->count();
    }

    public function getLateCount(): int
    {
        return Attendance::where('date', Carbon::today()->toDateString())
            ->where('status', 'late')
            ->count();
    }

    public function getAttendanceStatus(string $type, Carbon $time): string
    {
        $attendanceTime = AttendanceTime::where('type', $type)
            ->where('is_active', true)
            ->first();

        if (! $attendanceTime) {
            return 'present';
        }

        $start = Carbon::parse($attendanceTime->start_time);
        $end = Carbon::parse($attendanceTime->end_time);

        if ($type === 'in' && $time->gt($end)) {
            return 'late';
        }

        if ($type === 'out' && $time->lt($start)) {
            return 'early';
        }

        return 'present';
    }

    public function getMonthlyAttendanceReport(int $userId, int $year, int $month)
    {
        return Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();
    }
}

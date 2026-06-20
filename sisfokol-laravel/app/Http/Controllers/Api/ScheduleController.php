<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function today(Request $request)
    {
        $user = $request->user();
        $employee = $user->userable;

        $dayNumber = Carbon::now()->dayOfWeek;

        $schedules = Schedule::with(['classroom', 'subject', 'room', 'timeSlot'])
            ->where('employee_id', $employee?->id)
            ->where('day_id', $dayNumber)
            ->orderBy('time_slot_id')
            ->get();

        return response()->json([
            'date' => Carbon::now()->format('Y-m-d'),
            'day' => Carbon::now()->locale('id')->dayName,
            'schedules' => $schedules,
        ]);
    }
}

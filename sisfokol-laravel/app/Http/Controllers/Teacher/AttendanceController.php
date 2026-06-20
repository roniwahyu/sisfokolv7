<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceTime;
use App\Models\Employee;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function scan()
    {
        return view('teacher.attendance.scan');
    }

    public function storeScan(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'type' => 'required|in:in,out',
        ]);

        $code = $request->code;
        $type = $request->type;

        $person = Student::where('nis', $code)->first()
            ?? Employee::where('code', $code)->first();

        if (! $person) {
            return back()->with('error', 'Kode tidak ditemukan.');
        }

        $user = $person->user;
        if (! $user) {
            return back()->with('error', 'Pengguna tidak terhubung dengan data ini.');
        }

        $attendanceTime = AttendanceTime::where('type', $type)
            ->where('is_active', true)
            ->first();

        $now = Carbon::now();
        $status = 'present';

        if ($attendanceTime) {
            $start = Carbon::parse($attendanceTime->start_time);
            $end = Carbon::parse($attendanceTime->end_time);

            if ($type === 'in' && $now->gt($end)) {
                $status = 'late';
            } elseif ($type === 'out' && $now->lt($start)) {
                $status = 'early';
            }
        }

        Attendance::create([
            'user_id' => $user->id,
            'attendable_type' => get_class($person),
            'attendable_id' => $person->id,
            'date' => $now->toDateString(),
            'time' => $now->toTimeString(),
            'type' => $type,
            'source' => 'qr',
            'status' => $status,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Presensi {$type} untuk {$person->name} berhasil.");
    }

    public function manual(Request $request)
    {
        $employee = Auth::user()->userable;
        $classrooms = $employee?->classrooms ?? collect();

        return view('teacher.attendance.manual', compact('classrooms'));
    }
}

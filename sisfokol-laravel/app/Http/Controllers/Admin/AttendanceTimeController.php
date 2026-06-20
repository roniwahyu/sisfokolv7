<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceTimeRequest;
use App\Models\AcademicYear;
use App\Models\AttendanceTime;
use Illuminate\Http\Request;

class AttendanceTimeController extends Controller
{
    public function index()
    {
        $attendanceTimes = AttendanceTime::with('academicYear')->latest()->paginate(20);

        return view('admin.attendance-times.index', compact('attendanceTimes'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();

        return view('admin.attendance-times.create', compact('academicYears'));
    }

    public function store(StoreAttendanceTimeRequest $request)
    {
        AttendanceTime::create($request->validated());

        return redirect()->route('admin.attendance-times.index')->with('success', 'Waktu presensi berhasil ditambahkan.');
    }

    public function edit(AttendanceTime $attendanceTime)
    {
        $academicYears = AcademicYear::all();

        return view('admin.attendance-times.edit', compact('attendanceTime', 'academicYears'));
    }

    public function update(StoreAttendanceTimeRequest $request, AttendanceTime $attendanceTime)
    {
        $attendanceTime->update($request->validated());

        return redirect()->route('admin.attendance-times.index')->with('success', 'Waktu presensi berhasil diperbarui.');
    }

    public function destroy(AttendanceTime $attendanceTime)
    {
        $attendanceTime->delete();

        return redirect()->route('admin.attendance-times.index')->with('success', 'Waktu presensi berhasil dihapus.');
    }
}

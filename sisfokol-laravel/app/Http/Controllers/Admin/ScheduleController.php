<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Day;
use App\Models\Employee;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with(['academicYear', 'classroom', 'subject', 'teacher', 'room', 'day', 'timeSlot'])
            ->latest()
            ->paginate(30);

        return view('admin.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $classrooms = Classroom::all();
        $subjects = Subject::all();
        $teachers = Employee::where('position', 'guru')->get();
        $rooms = Room::all();
        $days = Day::all();
        $timeSlots = TimeSlot::orderBy('order')->get();

        return view('admin.schedules.create', compact(
            'academicYears', 'classrooms', 'subjects', 'teachers', 'rooms', 'days', 'timeSlots'
        ));
    }

    public function store(StoreScheduleRequest $request)
    {
        Schedule::create($request->validated());

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule)
    {
        $academicYears = AcademicYear::all();
        $classrooms = Classroom::all();
        $subjects = Subject::all();
        $teachers = Employee::where('position', 'guru')->get();
        $rooms = Room::all();
        $days = Day::all();
        $timeSlots = TimeSlot::orderBy('order')->get();

        return view('admin.schedules.edit', compact(
            'schedule', 'academicYears', 'classrooms', 'subjects', 'teachers', 'rooms', 'days', 'timeSlots'
        ));
    }

    public function update(StoreScheduleRequest $request, Schedule $schedule)
    {
        $schedule->update($request->validated());

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil dihapus.');
    }
}

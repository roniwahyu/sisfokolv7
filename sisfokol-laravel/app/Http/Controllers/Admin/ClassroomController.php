<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassroomRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::with(['academicYear', 'homeroomTeacher'])
            ->latest()
            ->paginate(20);

        return view('admin.classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $teachers = Employee::where('position', 'guru')->get();

        return view('admin.classrooms.create', compact('academicYears', 'teachers'));
    }

    public function store(StoreClassroomRequest $request)
    {
        Classroom::create($request->validated());

        return redirect()->route('admin.classrooms.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Classroom $classroom)
    {
        $academicYears = AcademicYear::all();
        $teachers = Employee::where('position', 'guru')->get();

        return view('admin.classrooms.edit', compact('classroom', 'academicYears', 'teachers'));
    }

    public function update(StoreClassroomRequest $request, Classroom $classroom)
    {
        $classroom->update($request->validated());

        return redirect()->route('admin.classrooms.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom)
    {
        $classroom->delete();

        return redirect()->route('admin.classrooms.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }
}

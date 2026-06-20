<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentViolationRequest;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Student;
use App\Models\StudentViolation;
use App\Models\ViolationPoint;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function index()
    {
        $violations = StudentViolation::with(['student', 'violationPoint.violationType', 'reporter'])
            ->latest()
            ->paginate(20);

        return view('counselor.violations.index', compact('violations'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $violationPoints = ViolationPoint::with('violationType')->get();
        $employees = Employee::where('position', 'guru')->get();

        return view('counselor.violations.create', compact('academicYears', 'students', 'violationPoints', 'employees'));
    }

    public function store(StoreStudentViolationRequest $request)
    {
        StudentViolation::create($request->validated());

        return redirect()->route('counselor.violations.index')->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function edit(StudentViolation $violation)
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $violationPoints = ViolationPoint::with('violationType')->get();
        $employees = Employee::where('position', 'guru')->get();

        return view('counselor.violations.edit', compact('violation', 'academicYears', 'students', 'violationPoints', 'employees'));
    }

    public function update(StoreStudentViolationRequest $request, StudentViolation $violation)
    {
        $violation->update($request->validated());

        return redirect()->route('counselor.violations.index')->with('success', 'Pelanggaran berhasil diperbarui.');
    }

    public function destroy(StudentViolation $violation)
    {
        $violation->delete();

        return redirect()->route('counselor.violations.index')->with('success', 'Pelanggaran berhasil dihapus.');
    }
}

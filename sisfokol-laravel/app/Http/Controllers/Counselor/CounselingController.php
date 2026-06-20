<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentCounselingRequest;
use App\Models\AcademicYear;
use App\Models\CounselingType;
use App\Models\CounselorTeacher;
use App\Models\Student;
use App\Models\StudentCounseling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounselingController extends Controller
{
    public function index()
    {
        $counselings = StudentCounseling::with(['student', 'counselingType', 'counselor.employee'])
            ->latest()
            ->paginate(20);

        return view('counselor.counselings.index', compact('counselings'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $counselingTypes = CounselingType::all();
        $counselors = CounselorTeacher::with('employee')->get();

        return view('counselor.counselings.create', compact('academicYears', 'students', 'counselingTypes', 'counselors'));
    }

    public function store(StoreStudentCounselingRequest $request)
    {
        StudentCounseling::create($request->validated());

        return redirect()->route('counselor.counselings.index')->with('success', 'Pembinaan berhasil dicatat.');
    }

    public function edit(StudentCounseling $counseling)
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $counselingTypes = CounselingType::all();
        $counselors = CounselorTeacher::with('employee')->get();

        return view('counselor.counselings.edit', compact('counseling', 'academicYears', 'students', 'counselingTypes', 'counselors'));
    }

    public function update(StoreStudentCounselingRequest $request, StudentCounseling $counseling)
    {
        $counseling->update($request->validated());

        return redirect()->route('counselor.counselings.index')->with('success', 'Pembinaan berhasil diperbarui.');
    }

    public function destroy(StudentCounseling $counseling)
    {
        $counseling->delete();

        return redirect()->route('counselor.counselings.index')->with('success', 'Pembinaan berhasil dihapus.');
    }
}

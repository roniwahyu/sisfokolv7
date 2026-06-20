<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentSavingRequest;
use App\Models\SavingsSetting;
use App\Models\Student;
use App\Models\StudentSaving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentSavingController extends Controller
{
    public function index()
    {
        $savings = StudentSaving::with(['student', 'treasurer'])->latest()->paginate(20);

        return view('finance.student-savings.index', compact('savings'));
    }

    public function create()
    {
        $students = Student::all();
        $settings = SavingsSetting::where('academic_year_id', AcademicYear::active()?->id)->first();

        return view('finance.student-savings.create', compact('students', 'settings'));
    }

    public function store(StoreStudentSavingRequest $request)
    {
        $data = $request->validated();
        $student = Student::find($data['student_id']);

        $lastBalance = StudentSaving::where('student_id', $student->id)->latest()->value('balance') ?? 0;

        $data['academic_year_id'] = $student->academic_year_id;
        $data['classroom_id'] = $student->classroom_id;
        $data['treasurer_id'] = Auth::user()->userable?->treasurer?->id;
        $data['balance'] = $data['is_debit']
            ? $lastBalance + $data['amount']
            : $lastBalance - $data['amount'];

        StudentSaving::create($data);

        return redirect()->route('finance.student-savings.index')->with('success', 'Tabungan berhasil dicatat.');
    }

    public function destroy(StudentSaving $studentSaving)
    {
        $studentSaving->delete();

        return redirect()->route('finance.student-savings.index')->with('success', 'Tabungan berhasil dihapus.');
    }
}

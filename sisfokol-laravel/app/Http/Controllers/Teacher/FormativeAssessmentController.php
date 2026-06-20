<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormativeAssessmentRequest;
use App\Models\Classroom;
use App\Models\FormativeAssessment;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormativeAssessmentController extends Controller
{
    public function index()
    {
        $assessments = FormativeAssessment::with(['subject', 'classroom'])
            ->whereHas('subject', fn ($q) => $q->whereIn('id', Auth::user()->userable?->subjects()->pluck('subjects.id') ?? []))
            ->latest()
            ->paginate(20);

        return view('teacher.formative-assessments.index', compact('assessments'));
    }

    public function create()
    {
        $subjects = Auth::user()->userable?->subjects() ?? collect();
        $classrooms = Classroom::all();

        return view('teacher.formative-assessments.create', compact('subjects', 'classrooms'));
    }

    public function store(StoreFormativeAssessmentRequest $request)
    {
        FormativeAssessment::create($request->validated());

        return redirect()->route('teacher.formative-assessments.index')->with('success', 'Asesmen formatif berhasil ditambahkan.');
    }

    public function show(FormativeAssessment $formativeAssessment)
    {
        $students = Student::where('classroom_id', $formativeAssessment->classroom_id)->get();

        return view('teacher.formative-assessments.show', compact('formativeAssessment', 'students'));
    }
}

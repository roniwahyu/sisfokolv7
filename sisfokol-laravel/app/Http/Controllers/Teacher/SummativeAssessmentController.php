<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSummativeAssessmentRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SummativeAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SummativeAssessmentController extends Controller
{
    public function index()
    {
        $assessments = SummativeAssessment::with(['subject', 'classroom'])
            ->whereHas('subject', fn ($q) => $q->whereIn('id', Auth::user()->userable?->subjects()->pluck('subjects.id') ?? []))
            ->latest()
            ->paginate(20);

        return view('teacher.summative-assessments.index', compact('assessments'));
    }

    public function create()
    {
        $subjects = Auth::user()->userable?->subjects() ?? collect();
        $classrooms = Classroom::all();

        return view('teacher.summative-assessments.create', compact('subjects', 'classrooms'));
    }

    public function store(StoreSummativeAssessmentRequest $request)
    {
        SummativeAssessment::create($request->validated());

        return redirect()->route('teacher.summative-assessments.index')->with('success', 'Asesmen sumatif berhasil ditambahkan.');
    }

    public function show(SummativeAssessment $summativeAssessment)
    {
        $students = Student::where('classroom_id', $summativeAssessment->classroom_id)->get();

        return view('teacher.summative-assessments.show', compact('summativeAssessment', 'students'));
    }
}

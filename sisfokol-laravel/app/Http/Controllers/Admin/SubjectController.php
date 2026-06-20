<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\SubjectType;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with(['academicYear', 'subjectType'])->latest()->paginate(20);

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $subjectTypes = SubjectType::all();

        return view('admin.subjects.create', compact('academicYears', 'subjectTypes'));
    }

    public function store(StoreSubjectRequest $request)
    {
        Subject::create($request->validated());

        return redirect()->route('admin.subjects.index')->with('success', 'Mapel berhasil ditambahkan.');
    }

    public function edit(Subject $subject)
    {
        $academicYears = AcademicYear::all();
        $subjectTypes = SubjectType::all();

        return view('admin.subjects.edit', compact('subject', 'academicYears', 'subjectTypes'));
    }

    public function update(StoreSubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());

        return redirect()->route('admin.subjects.index')->with('success', 'Mapel berhasil diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Mapel berhasil dihapus.');
    }
}

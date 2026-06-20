<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCurriculumCompetencyRequest;
use App\Models\AcademicYear;
use App\Models\CurriculumCompetency;
use App\Models\Subject;
use Illuminate\Http\Request;

class CurriculumCompetencyController extends Controller
{
    public function index()
    {
        $competencies = CurriculumCompetency::with(['academicYear', 'subject'])
            ->latest()
            ->paginate(20);

        return view('teacher.competencies.index', compact('competencies'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $subjects = Subject::all();

        return view('teacher.competencies.create', compact('academicYears', 'subjects'));
    }

    public function store(StoreCurriculumCompetencyRequest $request)
    {
        CurriculumCompetency::create($request->validated());

        return redirect()->route('teacher.competencies.index')->with('success', 'Tujuan Pembelajaran berhasil ditambahkan.');
    }

    public function edit(CurriculumCompetency $competency)
    {
        $academicYears = AcademicYear::all();
        $subjects = Subject::all();

        return view('teacher.competencies.edit', compact('competency', 'academicYears', 'subjects'));
    }

    public function update(StoreCurriculumCompetencyRequest $request, CurriculumCompetency $competency)
    {
        $competency->update($request->validated());

        return redirect()->route('teacher.competencies.index')->with('success', 'Tujuan Pembelajaran berhasil diperbarui.');
    }

    public function destroy(CurriculumCompetency $competency)
    {
        $competency->delete();

        return redirect()->route('teacher.competencies.index')->with('success', 'Tujuan Pembelajaran berhasil dihapus.');
    }
}

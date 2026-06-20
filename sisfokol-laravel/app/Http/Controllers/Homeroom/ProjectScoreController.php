<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectScore;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectScoreController extends Controller
{
    public function index()
    {
        $classroom = Auth::user()->userable?->homeroomClass;
        $projects = Project::where('classroom_id', $classroom?->id)->latest()->paginate(20);

        return view('homeroom.project-scores.index', compact('projects', 'classroom'));
    }

    public function show(Project $project)
    {
        $students = Student::where('classroom_id', $project->classroom_id)->get();
        $scores = ProjectScore::where('project_id', $project->id)->get()->keyBy('student_id');

        return view('homeroom.project-scores.show', compact('project', 'students', 'scores'));
    }

    public function store(Request $request, Project $project)
    {
        foreach ($request->scores as $studentId => $score) {
            ProjectScore::updateOrCreate(
                ['project_id' => $project->id, 'student_id' => $studentId],
                ['score' => $score, 'predicate' => $this->getPredicate($score)]
            );
        }

        return back()->with('success', 'Nilai proyek berhasil disimpan.');
    }

    private function getPredicate(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            default => 'D',
        };
    }
}

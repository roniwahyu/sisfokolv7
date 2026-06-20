<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $classroom = Auth::user()->userable?->homeroomClass;
        $projects = Project::with(['details.competency', 'scores.student'])
            ->where('classroom_id', $classroom?->id)
            ->latest()
            ->paginate(20);

        return view('homeroom.projects.index', compact('projects', 'classroom'));
    }
}

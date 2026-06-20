<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentAchievementRequest;
use App\Models\AcademicYear;
use App\Models\AchievementType;
use App\Models\Student;
use App\Models\StudentAchievement;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index()
    {
        $achievements = StudentAchievement::with(['student', 'achievementType'])->latest()->paginate(20);

        return view('counselor.achievements.index', compact('achievements'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $achievementTypes = AchievementType::all();

        return view('counselor.achievements.create', compact('academicYears', 'students', 'achievementTypes'));
    }

    public function store(StoreStudentAchievementRequest $request)
    {
        StudentAchievement::create($request->validated());

        return redirect()->route('counselor.achievements.index')->with('success', 'Prestasi berhasil dicatat.');
    }

    public function edit(StudentAchievement $achievement)
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $achievementTypes = AchievementType::all();

        return view('counselor.achievements.edit', compact('achievement', 'academicYears', 'students', 'achievementTypes'));
    }

    public function update(StoreStudentAchievementRequest $request, StudentAchievement $achievement)
    {
        $achievement->update($request->validated());

        return redirect()->route('counselor.achievements.index')->with('success', 'Prestasi berhasil diperbarui.');
    }

    public function destroy(StudentAchievement $achievement)
    {
        $achievement->delete();

        return redirect()->route('counselor.achievements.index')->with('success', 'Prestasi berhasil dihapus.');
    }
}

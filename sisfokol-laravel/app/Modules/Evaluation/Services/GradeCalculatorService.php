<?php

namespace App\Modules\Evaluation\Services;

use App\Models\AcademicYear;
use App\Models\FormativeAssessmentScore;
use App\Models\Student;
use App\Models\StudentSemesterScore;
use App\Models\SubjectDescription;
use App\Models\SummativeAssessmentScore;
use App\Modules\Academic\Models\Semester;
use App\Support\TenantContext;

class GradeCalculatorService
{
    /**
     * Calculate formative assessment average for a student.
     */
    public function calculateFormativeAverage(
        Student $student,
        int $subjectId,
        int $classroomId,
        AcademicYear $academicYear,
        Semester $semester
    ): float {
        $scores = FormativeAssessmentScore::where('student_id', $student->id)
            ->whereHas('assessment', function ($query) use ($subjectId, $classroomId, $academicYear, $semester) {
                $query->where('subject_id', $subjectId)
                    ->where('classroom_id', $classroomId)
                    ->where('academic_year_id', $academicYear->id)
                    ->whereBetween('assessment_date', [$semester->tanggal_mulai, $semester->tanggal_selesai]);
            })->pluck('score');

        if ($scores->isEmpty()) {
            return 0.0;
        }

        return round($scores->average(), 2);
    }

    /**
     * Calculate summative assessment average for a student.
     */
    public function calculateSummativeAverage(
        Student $student,
        int $subjectId,
        int $classroomId,
        AcademicYear $academicYear,
        Semester $semester
    ): float {
        $scores = SummativeAssessmentScore::where('student_id', $student->id)
            ->whereHas('assessment', function ($query) use ($subjectId, $classroomId, $academicYear, $semester) {
                $query->where('subject_id', $subjectId)
                    ->where('classroom_id', $classroomId)
                    ->where('academic_year_id', $academicYear->id)
                    ->whereBetween('assessment_date', [$semester->tanggal_mulai, $semester->tanggal_selesai]);
            })->pluck('score');

        if ($scores->isEmpty()) {
            return 0.0;
        }

        return round($scores->average(), 2);
    }

    /**
     * Calculate final semester grade and determine predicate.
     */
    public function calculateSemesterScore(
        Student $student,
        int $subjectId,
        int $classroomId,
        AcademicYear $academicYear,
        Semester $semester
    ): array {
        $avgFormative = $this->calculateFormativeAverage($student, $subjectId, $classroomId, $academicYear, $semester);
        $avgSummative = $this->calculateSummativeAverage($student, $subjectId, $classroomId, $academicYear, $semester);

        // Load weights from tenant settings, falling back to 0.40 and 0.60
        $weightFormative = floatval(app(TenantContext::class)->weight_formative ?? 0.40);
        $weightSummative = floatval(app(TenantContext::class)->weight_summative ?? 0.60);

        $finalScore = ($avgFormative * $weightFormative) + ($avgSummative * $weightSummative);
        $finalScore = round($finalScore, 2);
        $predicate = $this->determinePredicate($finalScore);

        // Try to fetch narrative description
        $descObj = SubjectDescription::where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYear->id)
            ->where('category', $predicate)
            ->first();

        $description = $descObj ? $descObj->description : "Menunjukkan penguasaan materi dengan predikat {$predicate}.";

        return [
            'formative_average' => $avgFormative,
            'summative_average' => $avgSummative,
            'final_score' => $finalScore,
            'predicate' => $predicate,
            'description' => $description,
        ];
    }

    /**
     * Determine predicate letter based on score.
     */
    public function determinePredicate(float $score): string
    {
        if ($score >= 90) {
            return 'A';
        } elseif ($score >= 80) {
            return 'B';
        } elseif ($score >= 70) {
            return 'C';
        } else {
            return 'D';
        }
    }

    /**
     * Calculate and save student semester score.
     */
    public function saveSemesterScore(
        Student $student,
        int $subjectId,
        int $classroomId,
        AcademicYear $academicYear,
        Semester $semester
    ): StudentSemesterScore {
        $result = $this->calculateSemesterScore($student, $subjectId, $classroomId, $academicYear, $semester);

        return StudentSemesterScore::updateOrCreate([
            'tenant_id' => $student->tenant_id,
            'academic_year_id' => $academicYear->id,
            'student_id' => $student->id,
            'subject_id' => $subjectId,
            'semester' => $semester->nama,
        ], [
            'score' => $result['final_score'],
            'predicate' => $result['predicate'],
            'description' => $result['description'],
        ]);
    }
}

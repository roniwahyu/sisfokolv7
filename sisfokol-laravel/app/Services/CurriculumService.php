<?php

namespace App\Services;

use App\Models\CurriculumCompetency;
use App\Models\Project;
use App\Models\Subject;

class CurriculumService
{
    public function getCompetenciesBySubject(int $subjectId, ?string $phase = null)
    {
        $query = CurriculumCompetency::with('learningMaterials')->where('subject_id', $subjectId);

        if ($phase) {
            $query->where('phase', $phase);
        }

        return $query->get();
    }

    public function getProjectsByClassroom(int $classroomId, ?int $academicYearId = null)
    {
        $query = Project::with(['details.competency', 'scores.student'])
            ->where('classroom_id', $classroomId);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->get();
    }

    public function calculatePredicate(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            default => 'D',
        };
    }
}

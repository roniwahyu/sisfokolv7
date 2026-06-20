<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentCounseling extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'counseling_type_id',
        'counselor_teacher_id',
        'date',
        'description',
        'follow_up',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function counselingType(): BelongsTo
    {
        return $this->belongsTo(CounselingType::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(CounselorTeacher::class, 'counselor_teacher_id');
    }
}

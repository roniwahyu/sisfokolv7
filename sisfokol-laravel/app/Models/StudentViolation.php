<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentViolation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'violation_point_id',
        'employee_id',
        'date',
        'description',
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

    public function violationPoint(): BelongsTo
    {
        return $this->belongsTo(ViolationPoint::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

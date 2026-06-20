<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExtracurricular extends Model
{
    use HasFactory;

    protected $table = 'student_extracurricular';

    protected $fillable = [
        'student_id',
        'extracurricular_id',
        'academic_year_id',
        'score',
        'description',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

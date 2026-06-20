<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'subject_type_id',
        'code',
        'name',
        'description',
        'is_exam',
        'phase',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'is_exam' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subjectType(): BelongsTo
    {
        return $this->belongsTo(SubjectType::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(SubjectDescription::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_subject')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'academic_year_id',
        'classroom_id',
        'nis',
        'nisn',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'email',
        'father_name',
        'mother_name',
        'parent_phone',
        'photo_path',
        'qrcode_path',
        'is_active',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function extracurriculars(): BelongsToMany
    {
        return $this->belongsToMany(Extracurricular::class, 'student_extracurricular')
            ->withPivot('academic_year_id', 'score', 'description')
            ->withTimestamps();
    }
}

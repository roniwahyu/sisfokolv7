<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'email',
        'position',
        'status',
        'join_date',
        'photo_path',
        'qrcode_path',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homeroomClass(): HasOne
    {
        return $this->hasOne(Classroom::class, 'homeroom_teacher_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(TeacherAgenda::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'employee_subject')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }
}

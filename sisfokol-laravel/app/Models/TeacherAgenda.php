<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherAgenda extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'schedule_id',
        'employee_id',
        'date',
        'topic',
        'material',
        'student_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

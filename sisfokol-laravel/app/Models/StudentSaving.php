<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentSaving extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'classroom_id',
        'student_id',
        'date',
        'is_debit',
        'amount',
        'balance',
        'treasurer_id',
        'note',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_debit' => 'boolean',
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function treasurer(): BelongsTo
    {
        return $this->belongsTo(Treasurer::class);
    }
}

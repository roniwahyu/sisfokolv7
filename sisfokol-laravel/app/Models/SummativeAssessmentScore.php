<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SummativeAssessmentScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'student_id',
        'score',
        'predicate',
        'note',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(SummativeAssessment::class, 'assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SummativeAssessmentScoreDetail::class, 'score_id');
    }
}

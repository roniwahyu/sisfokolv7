<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SummativeAssessmentScoreDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'score_id',
        'competency_id',
        'score',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function score(): BelongsTo
    {
        return $this->belongsTo(SummativeAssessmentScore::class, 'score_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(CurriculumCompetency::class, 'competency_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormativeAssessmentScoreDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'score_id',
        'material_id',
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
        return $this->belongsTo(FormativeAssessmentScore::class, 'score_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(CurriculumLearningMaterial::class, 'material_id');
    }
}

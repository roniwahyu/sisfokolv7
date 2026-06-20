<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumLearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'competency_id',
        'code',
        'description',
        'legacy_id',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(CurriculumCompetency::class, 'competency_id');
    }
}

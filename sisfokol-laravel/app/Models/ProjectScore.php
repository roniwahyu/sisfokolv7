<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function processScores(): HasMany
    {
        return $this->hasMany(ProjectProcessScore::class, 'project_score_id');
    }
}

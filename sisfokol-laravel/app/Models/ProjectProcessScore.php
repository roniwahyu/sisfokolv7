<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectProcessScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_score_id',
        'dimension',
        'score',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function projectScore(): BelongsTo
    {
        return $this->belongsTo(ProjectScore::class, 'project_score_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'competency_id',
        'description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(CurriculumCompetency::class, 'competency_id');
    }
}

<?php

namespace App\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumCompetency extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = [
        'academic_year_id',
        'subject_id',
        'phase',
        'code',
        'description',
        'legacy_id',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function learningMaterials(): HasMany
    {
        return $this->hasMany(CurriculumLearningMaterial::class, 'competency_id');
    }
}

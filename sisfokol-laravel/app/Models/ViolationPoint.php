<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViolationPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'violation_type_id',
        'name',
        'point',
        'description',
    ];

    public function violationType(): BelongsTo
    {
        return $this->belongsTo(ViolationType::class);
    }
}

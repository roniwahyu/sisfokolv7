<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'min_debit',
        'max_credit',
        'min_balance',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'min_debit' => 'decimal:2',
            'max_credit' => 'decimal:2',
            'min_balance' => 'decimal:2',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

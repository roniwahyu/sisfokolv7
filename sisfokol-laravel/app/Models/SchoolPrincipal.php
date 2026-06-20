<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolPrincipal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'appointed_at',
    ];

    protected function casts(): array
    {
        return [
            'appointed_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounselorTeacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'certificate_number',
        'certified_at',
    ];

    protected function casts(): array
    {
        return [
            'certified_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

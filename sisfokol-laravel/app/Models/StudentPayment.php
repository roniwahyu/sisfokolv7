<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'treasurer_id',
        'invoice_number',
        'payment_date',
        'total',
        'payment_method',
        'note',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function treasurer(): BelongsTo
    {
        return $this->belongsTo(Treasurer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(StudentPaymentDetail::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPaymentDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_payment_id',
        'student_bill_id',
        'amount',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function studentPayment(): BelongsTo
    {
        return $this->belongsTo(StudentPayment::class);
    }

    public function studentBill(): BelongsTo
    {
        return $this->belongsTo(StudentBill::class);
    }
}

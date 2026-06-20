<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'payment_item_id',
        'period',
        'amount',
        'paid',
        'remaining',
        'due_date',
        'status',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid' => 'decimal:2',
            'remaining' => 'decimal:2',
            'due_date' => 'date',
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

    public function paymentItem(): BelongsTo
    {
        return $this->belongsTo(PaymentItem::class);
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(StudentPaymentDetail::class);
    }

    public function updateStatus(): void
    {
        if ($this->paid >= $this->amount) {
            $this->status = 'paid';
        } elseif ($this->paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }
        $this->remaining = max(0, $this->amount - $this->paid);
    }
}

<?php

namespace App\Services;

use App\Models\StudentPayment;

class FinanceService
{
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $last = StudentPayment::whereDate('created_at', today())->count() + 1;

        return "{$prefix}-{$date}-".str_pad($last, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalPaymentToday(): float
    {
        return (float) StudentPayment::whereDate('payment_date', today())->sum('total');
    }

    public function getTotalOutstanding(): float
    {
        return (float) \App\Models\StudentBill::sum('remaining');
    }
}

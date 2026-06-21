<?php

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\Pembayaran;

class PaymentReceived
{
    public function __construct(public Pembayaran $pembayaran) {}
}

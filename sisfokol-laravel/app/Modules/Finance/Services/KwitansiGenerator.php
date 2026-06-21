<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Pembayaran;

class KwitansiGenerator
{
    /**
     * Generate unique no_nota per tenant: format "INV-YYYYMMDD-XXXX" where XXXX = sequence.
     */
    public function generate(int $tenantId): string
    {
        $today = now()->format('Ymd');
        $prefix = "INV-{$today}-";
        
        $count = Pembayaran::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('no_nota', 'like', "{$prefix}%")
            ->count();
            
        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $seq;
    }
}

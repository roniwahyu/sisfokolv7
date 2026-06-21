<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\Pembayaran;
use App\Modules\Finance\Models\TagihanSiswa;

class PembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.*') 
            || $user->can('finance.student-payment.*')
            || $user->can('finance.student-bill.*')
            || $user->can('finance.student-bill.view')
            || $user->can('finance.student-payment.view');
    }

    public function viewTagihan(User $user, TagihanSiswa $tagihan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Student can view their own bill
        if ($user->hasRole('student')) {
            // Find student associated with user (we can map username or userable, depending on project structure)
            // Let's assume $user->userable_id maps to Siswa if userable_type is Siswa
            $isOwn = $user->userable_type === 'App\Modules\Academic\Models\Siswa' && $user->userable_id === $tagihan->siswa_id;
            return $isOwn && $user->tenant_id === $tagihan->tenant_id;
        }

        return ($user->can('finance.*') 
            || $user->can('finance.student-bill.*')
            || $user->can('finance.student-bill.view'))
            && $user->tenant_id === $tagihan->tenant_id;
    }

    public function viewPembayaran(User $user, Pembayaran $pembayaran): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole('student')) {
            $isOwn = $user->userable_type === 'App\Modules\Academic\Models\Siswa' && $user->userable_id === $pembayaran->siswa_id;
            return $isOwn && $user->tenant_id === $pembayaran->tenant_id;
        }

        return ($user->can('finance.*') 
            || $user->can('finance.student-payment.*')
            || $user->can('finance.student-payment.view'))
            && $user->tenant_id === $pembayaran->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('finance.*') || $user->can('finance.student-payment.*');
    }
}

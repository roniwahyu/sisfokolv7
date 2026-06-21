<?php

namespace App\Modules\Finance\Services;

use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Models\TabunganSiswa;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TabunganMutasiService
{
    /**
     * Get or create a savings account for the given Siswa.
     */
    public function getOrCreateAccount(Siswa $siswa): TabunganSiswa
    {
        return DB::transaction(function () use ($siswa) {
            // Find existing
            $existing = TabunganSiswa::withoutGlobalScope('tenant')
                ->where('tenant_id', $siswa->tenant_id)
                ->where('siswa_id', $siswa->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            // Generate account number: 100 + TenantID (3 digits) + SiswaID (6 digits)
            $noRekening = '100' . str_pad($siswa->tenant_id, 3, '0', STR_PAD_LEFT) . str_pad($siswa->id, 6, '0', STR_PAD_LEFT);

            return TabunganSiswa::create([
                'tenant_id' => $siswa->tenant_id,
                'siswa_id' => $siswa->id,
                'no_rekening' => $noRekening,
                'saldo' => 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Deposit funds into savings account.
     */
    public function setor(TabunganSiswa $tabungan, float $nominal): TabunganSiswa
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($tabungan, $nominal) {
            // Pessimistic locking of the account
            $account = TabunganSiswa::withoutGlobalScope('tenant')
                ->where('id', $tabungan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $account->saldo += $nominal;
            $account->updated_by = auth()->id();
            $account->save();

            return $account;
        });
    }

    /**
     * Withdraw funds from savings account.
     */
    public function tarik(TabunganSiswa $tabungan, float $nominal): TabunganSiswa
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($tabungan, $nominal) {
            // Pessimistic locking of the account
            $account = TabunganSiswa::withoutGlobalScope('tenant')
                ->where('id', $tabungan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->saldo < $nominal) {
                throw new InvalidArgumentException('Saldo tidak mencukupi untuk melakukan penarikan.');
            }

            $account->saldo -= $nominal;
            $account->updated_by = auth()->id();
            $account->save();

            return $account;
        });
    }
}

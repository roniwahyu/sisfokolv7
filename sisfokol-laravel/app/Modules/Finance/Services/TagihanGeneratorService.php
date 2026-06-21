<?php

namespace App\Modules\Finance\Services;

use App\Modules\Academic\Models\Kelas;
use App\Modules\Academic\Models\KelasSiswa;
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Models\TagihanSiswa;
use Illuminate\Support\Facades\DB;

class TagihanGeneratorService
{
    /**
     * Generate SPP tagihan for all siswa in kelas for a given bulan. Idempotent via UNIQUE index.
     * Skips siswa who already have existing tagihan for that bulan.
     */
    public function generateSpp(TahunAjaran $tapel, Kelas $kelas, ItemPembayaran $item, int $bulan): int
    {
        $created = 0;
        DB::transaction(function () use ($tapel, $kelas, $item, $bulan, &$created) {
            $kelasSiswa = KelasSiswa::withoutGlobalScope('tenant')
                ->where('kelas_id', $kelas->id)
                ->where('tahun_ajaran_id', $tapel->id)
                ->where('tenant_id', $kelas->tenant_id)
                ->get();

            foreach ($kelasSiswa as $ks) {
                // Check existing
                $existing = TagihanSiswa::withoutGlobalScope('tenant')
                    ->where('tenant_id', $kelas->tenant_id)
                    ->where('siswa_id', $ks->siswa_id)
                    ->where('item_pembayaran_id', $item->id)
                    ->where('tahun_ajaran_id', $tapel->id)
                    ->where('bulan', $bulan)
                    ->first();
                
                if ($existing) {
                    continue; // Idempotent check
                }

                TagihanSiswa::withoutGlobalScope('tenant')->create([
                    'tenant_id'          => $kelas->tenant_id,
                    'siswa_id'           => $ks->siswa_id,
                    'item_pembayaran_id'  => $item->id,
                    'tahun_ajaran_id'    => $tapel->id,
                    'bulan'              => $bulan,
                    'nominal_tagihan'    => $item->nominal,
                    'nominal_bayar'      => 0,
                    'nominal_kurang'     => $item->nominal,
                    'lunas'              => false,
                    'created_by'         => auth()->id(),
                    'updated_by'         => auth()->id(),
                ]);
                $created++;
            }
        });
        return $created;
    }
}

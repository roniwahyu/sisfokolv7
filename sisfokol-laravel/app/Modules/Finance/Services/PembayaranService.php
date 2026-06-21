<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Events\PaymentReceived;
use App\Modules\Finance\Models\Pembayaran;
use App\Modules\Finance\Models\PembayaranRincian;
use App\Modules\Finance\Models\TagihanSiswa;
use App\Modules\Auth\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class PembayaranService
{
    public function __construct(
        private KwitansiGenerator $kwitansi,
        private AuditLogger $audit,
    ) {}

    /**
     * Pencatatan pembayaran dengan DB transaction + pessimistic locking.
     */
    public function bayar(Siswa $siswa, array $rincian, User $diterimaOleh): Pembayaran
    {
        return DB::transaction(function () use ($siswa, $rincian, $diterimaOleh) {
            // 1. Generate no_nota (unique per tenant)
            $noNota = $this->kwitansi->generate($siswa->tenant_id);
            $total = array_sum(array_column($rincian, 'jumlah'));

            // 2. Insert header pembayaran
            $pembayaran = Pembayaran::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $siswa->tenant_id,
                'siswa_id'     => $siswa->id,
                'no_nota'      => $noNota,
                'tanggal'      => now(),
                'total'        => $total,
                'diterima_oleh' => $diterimaOleh->id,
                'created_by'   => $diterimaOleh->id,
                'updated_by'   => $diterimaOleh->id,
            ]);

            // 3. Process each rincian with ROW-LEVEL LOCK
            foreach ($rincian as $r) {
                /** @var TagihanSiswa|null $tagihan */
                $tagihan = TagihanSiswa::withoutGlobalScope('tenant')
                    ->where('id', $r['tagihan_id'])
                    ->where('tenant_id', $siswa->tenant_id)
                    ->lockForUpdate()  // PESSIMISTIC LOCK — race-safe
                    ->first();

                if (! $tagihan) {
                    throw new \InvalidArgumentException("Tagihan #{$r['tagihan_id']} tidak ditemukan.");
                }

                // Clamp jumlah to remaining (don't overcharge)
                $jumlah = min((float) $r['jumlah'], (float) $tagihan->nominal_kurang);
                if ($jumlah <= 0) {
                    throw new \InvalidArgumentException("Tagihan #{$tagihan->id} sudah lunas atau jumlah tidak valid.");
                }

                // Insert rincian
                PembayaranRincian::withoutGlobalScope('tenant')->create([
                    'tenant_id'         => $siswa->tenant_id,
                    'pembayaran_id'     => $pembayaran->id,
                    'tagihan_siswa_id'  => $tagihan->id,
                    'jumlah'            => $jumlah,
                    'created_by'        => $diterimaOleh->id,
                    'updated_by'        => $diterimaOleh->id,
                ]);

                // Update tagihan (safe due to lock)
                $tagihan->nominal_bayar = (float) $tagihan->nominal_bayar + $jumlah;
                $tagihan->nominal_kurang = max(0.00, (float) $tagihan->nominal_kurang - $jumlah);
                $tagihan->lunas = $tagihan->nominal_kurang <= 0;
                if ($tagihan->lunas && ! $tagihan->tanggal_lunas) {
                    $tagihan->tanggal_lunas = now();
                }
                $tagihan->updated_by = $diterimaOleh->id;
                $tagihan->save();
            }

            // 4. Emit event
            event(new PaymentReceived($pembayaran));

            // 5. Audit log
            $this->audit->log(
                event: 'pembayaran.stored',
                user: $diterimaOleh,
                newValues: [
                    'pembayaran_id' => $pembayaran->id,
                    'no_nota' => $noNota,
                    'total' => $total,
                    'siswa_id' => $siswa->id,
                ],
                request: request(),
                oldValues: [],
                modelType: Pembayaran::class,
                modelId: $pembayaran->id
            );

            return $pembayaran;
        });
    }
}

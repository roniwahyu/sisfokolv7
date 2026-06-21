<?php

namespace App\Modules\Finance\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\TracksAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranRincian extends Model
{
    use BelongsToTenant, TracksAuditColumns;

    protected $table = 'pembayaran_rincian';

    protected $fillable = [
        'tenant_id',
        'pembayaran_id',
        'tagihan_siswa_id',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function tagihanSiswa(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class, 'tagihan_siswa_id');
    }
}

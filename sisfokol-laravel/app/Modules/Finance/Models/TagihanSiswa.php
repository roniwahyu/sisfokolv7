<?php

namespace App\Modules\Finance\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\TracksAuditColumns;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Academic\Models\Semester;
use App\Modules\Academic\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagihanSiswa extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $table = 'tagihan_siswa';

    protected $fillable = [
        'tenant_id',
        'siswa_id',
        'item_pembayaran_id',
        'tahun_ajaran_id',
        'semester_id',
        'bulan',
        'nominal_tagihan',
        'nominal_bayar',
        'nominal_kurang',
        'lunas',
        'tanggal_lunas',
    ];

    protected function casts(): array
    {
        return [
            'nominal_tagihan' => 'decimal:2',
            'nominal_bayar'   => 'decimal:2',
            'nominal_kurang'  => 'decimal:2',
            'lunas'           => 'boolean',
            'tanggal_lunas'   => 'date',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function itemPembayaran(): BelongsTo
    {
        return $this->belongsTo(ItemPembayaran::class, 'item_pembayaran_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}

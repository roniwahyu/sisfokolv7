<?php

namespace App\Modules\Finance\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\TracksAuditColumns;
use App\Modules\Academic\Models\Kelas;
use App\Modules\Academic\Models\Semester;
use App\Modules\Academic\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPembayaran extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $table = 'item_pembayaran';

    protected $fillable = [
        'tenant_id',
        'tahun_ajaran_id',
        'semester_id',
        'kelas_id',
        'nama',
        'jenis',
        'nominal',
        'periode',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }
}

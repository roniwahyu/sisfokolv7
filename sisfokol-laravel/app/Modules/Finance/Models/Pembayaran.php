<?php

namespace App\Modules\Finance\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\TracksAuditColumns;
use App\Modules\Academic\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembayaran extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $table = 'pembayaran';

    protected $fillable = [
        'tenant_id',
        'siswa_id',
        'no_nota',
        'tanggal',
        'total',
        'diterima_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total'   => 'decimal:2',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function diterimaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function rincian(): HasMany
    {
        return $this->hasMany(PembayaranRincian::class, 'pembayaran_id');
    }
}

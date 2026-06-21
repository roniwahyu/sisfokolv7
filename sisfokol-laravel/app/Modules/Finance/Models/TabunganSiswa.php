<?php

namespace App\Modules\Finance\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\TracksAuditColumns;
use App\Modules\Academic\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TabunganSiswa extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $table = 'tabungan_siswa';

    protected $fillable = [
        'tenant_id',
        'siswa_id',
        'no_rekening',
        'saldo',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}

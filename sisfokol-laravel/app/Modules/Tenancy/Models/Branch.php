<?php

namespace App\Modules\Tenancy\Models;

use App\Models\Traits\TracksAuditColumns;
use Illuminate\Database\Eloquent\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes, TracksAuditColumns;

    protected $fillable = ['tenant_id', 'nama', 'jenjang', 'alamat', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

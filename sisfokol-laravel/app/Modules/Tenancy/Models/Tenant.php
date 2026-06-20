<?php

namespace App\Modules\Tenancy\Models;

use App\Models\Traits\TracksAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes, TracksAuditColumns;

    protected $fillable = [
        'nama', 'npsn', 'domain', 'jenjang', 'alamat', 'telepon', 'email', 'logo_url', 'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class);
    }

    /** Helper: ambil setting by key */
    public function setting(string $key, mixed $default = null): mixed
    {
        $s = $this->settings()->where('key', $key)->first();
        return $s?->value ?? $default;
    }
}

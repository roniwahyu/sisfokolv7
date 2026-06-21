<?php

namespace App\Plugins\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plugin extends Model
{
    protected $table = 'plugins';
    protected $fillable = ['kode', 'nama', 'deskripsi', 'versi', 'is_core', 'provider_class', 'aktif_global'];

    protected function casts(): array
    {
        return ['is_core' => 'boolean', 'aktif_global' => 'boolean'];
    }

    public function tenantPlugins(): HasMany
    {
        return $this->hasMany(TenantPlugin::class);
    }
}

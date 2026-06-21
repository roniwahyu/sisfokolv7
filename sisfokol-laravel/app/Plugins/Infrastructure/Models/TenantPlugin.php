<?php

namespace App\Plugins\Infrastructure\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPlugin extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_plugins';

    protected $fillable = [
        'tenant_id',
        'plugin_id',
        'aktif',
        'pengaturan',
        'diaktifkan_oleh',
        'diaktifkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'pengaturan' => 'array',
            'diaktifkan_pada' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function diaktifkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diaktifkan_oleh');
    }
}

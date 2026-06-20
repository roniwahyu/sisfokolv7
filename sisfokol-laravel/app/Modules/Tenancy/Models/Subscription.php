<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['tenant_id', 'paket', 'mulai', 'berakhir', 'status'];

    protected function casts(): array
    {
        return ['mulai' => 'date', 'berakhir' => 'date'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

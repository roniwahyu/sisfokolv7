<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    protected $fillable = ['tenant_id', 'key', 'value'];
}

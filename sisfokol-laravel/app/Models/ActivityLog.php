<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_code',
        'user_name',
        'position',
        'job_title',
        'description',
        'menu',
        'ip_address',
        'is_read',
        'activity_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

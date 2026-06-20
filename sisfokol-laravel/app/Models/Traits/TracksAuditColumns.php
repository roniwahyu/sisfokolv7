<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * ADR-007: Auto-fill created_by/updated_by dari Auth::id().
 */
trait TracksAuditColumns
{
    public static function bootTracksAuditColumns(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (auth()->check() && empty($model->updated_by)) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function (Model $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}

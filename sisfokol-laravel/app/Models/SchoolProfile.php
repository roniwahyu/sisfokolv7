<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'npsn',
        'nss',
        'headmaster_name',
        'headmaster_nip',
        'latitude',
        'longitude',
        'google_map_url',
        'logo_path',
        'stamp_path',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }
}

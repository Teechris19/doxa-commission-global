<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conclave extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];
}

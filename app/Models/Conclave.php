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
        'whatsapp_link',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

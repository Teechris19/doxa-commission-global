<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupLocation extends Model
{
    protected $fillable = [
        'name',
        'address',
        'description',
        'contact_person',
        'contact_phone',
        'pickup_time',
        'latitude',
        'longitude',
        'chapter_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }


    public function transportRequests(): HasMany
    {
        return $this->hasMany(Transport::class, 'pickup_location_id');
    }
}

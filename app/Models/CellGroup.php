<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class CellGroup extends Model
{
    protected $fillable = [
        'chapter_id',
        'name',
        'description',
        'meeting_day',
        'meeting_time',
        'location',
        'address',
        'latitude',
        'longitude',
        'max_members',
        'phone',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'max_members' => 'integer',
        'meeting_time' => 'datetime:H:i',
    ];

    // Relationship to Chapter (parent)
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }


    // Cell has many leaders
    public function leaders(): HasMany
    {
        return $this->hasMany(CellLeader::class);
    }

    // Cell has many members
    public function members(): HasMany
    {
        return $this->hasMany(CellMember::class);
    }

    // Primary leader
    public function primaryLeader()
    {
        return $this->hasOne(CellLeader::class)->where('is_primary', true);
    }

    // Active members
    public function activeMembers()
    {
        return $this->members()->where('status', 'active');
    }

    // Check if cell is full
    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }

    // Get available spots
    public function availableSpots(): int
    {
        return max(0, $this->max_members - $this->activeMembers()->count());
    }
}

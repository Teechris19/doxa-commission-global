<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionReport extends Model
{
    protected $fillable = [
        'chapter_id',
        'created_by',
        'report_date',
        'location',
        'number_reached',
        'testimonies',
        'images',
        'expenses',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
        'expenses' => 'decimal:2',
        'images' => 'array',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function newMembers(): HasMany
    {
        return $this->hasMany(MissionNewMember::class);
    }

    public function outreachDetails(): HasMany
    {
        return $this->hasMany(MissionOutreachDetail::class);
    }
}

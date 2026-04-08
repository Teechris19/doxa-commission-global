<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionNewMember extends Model
{
    protected $fillable = [
        'chapter_id',
        'mission_report_id',
        'name',
        'phone',
        'email',
        'follow_up_status',
        'assigned_leader',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function missionReport(): BelongsTo
    {
        return $this->belongsTo(MissionReport::class);
    }
}

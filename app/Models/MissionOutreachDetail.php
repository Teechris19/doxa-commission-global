<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionOutreachDetail extends Model
{
    protected $fillable = [
        'chapter_id',
        'mission_report_id',
        'location',
        'team_members',
        'materials_used',
        'results',
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

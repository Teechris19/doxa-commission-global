<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrayerRequestTeam extends Model
{
    protected $fillable = [
        'chapter_id',
        'team_id'
    ];

    public function prayerRequest()
    {
        return $this->belongsTo(PrayerRequest::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

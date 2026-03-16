<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTeam extends Model
{
    protected $fillable = [
        'event_id',
        'team_id',
        'chapter_id'
    ];

    public function event()
    {
        return $this->belongsTo(Events::class, 'event_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

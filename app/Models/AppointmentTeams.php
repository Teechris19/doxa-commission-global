<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentTeams extends Model
{
    protected $fillable = [ 'team_id', 'chapter_id', 'free_time', 'free_day'];

    
    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

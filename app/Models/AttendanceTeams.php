<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceTeams extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'chapter_id',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

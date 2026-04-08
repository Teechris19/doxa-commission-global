<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Minute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'content', 'meeting_date', 'team_id', 'user_id', 'status', 'attendees', 'location',  'chapter_id'
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function reports()
    // {
    //     return $this->hasMany(MinuteReport::class);
    // }
}
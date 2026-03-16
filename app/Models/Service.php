<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'chapter_id',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
        'is_recurring',
        'special_date',
        'location',
        'livestream_url',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'special_date' => 'date',
    ];

    // Relationship to Chapter
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

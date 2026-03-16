<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventForm extends Model
{
    use SoftDeletes;

    protected $table = 'event_forms';

    protected $fillable = [
        'event_id',
        'chapter_id',
        'name',
        'email',
        'phone',
        'guests',
        'form',
        'answers',
        'notes',
        'status',
        'reminder_24h_sent_at',
        'reminder_2h_sent_at',
    ];

    protected $casts = [
        'form' => 'array',
        'answers' => 'array',
        'guests' => 'integer',
        'reminder_24h_sent_at' => 'datetime',
        'reminder_2h_sent_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Events::class, 'event_id');
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

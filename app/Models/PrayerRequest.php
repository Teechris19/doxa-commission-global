<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrayerRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'request',
        'is_addressed',
        'chapter_id',
        'user_id',
        'admin_notes',
        'notification_sent_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function scopeAddressed($query)
    {
        return $query->where('is_addressed', true);
    }

    public function scopeUnaddressed($query)
    {
        return $query->where('is_addressed', false);
    }
}

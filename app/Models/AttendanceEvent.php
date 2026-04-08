<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEvent extends Model
{
    protected $fillable = [
        'chapter_id',
        'name',
        'source_type',
        'event_id',
        'service_id',
        'location',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function event()
    {
        return $this->belongsTo(Events::class, 'event_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function sessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

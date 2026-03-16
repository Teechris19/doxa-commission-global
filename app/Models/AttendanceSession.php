<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'attendance_event_id',
        'date',
        'status',
        'location',
        'notes',
        'opened_by',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function attendanceEvent()
    {
        return $this->belongsTo(AttendanceEvent::class);
    }

    public function checkins()
    {
        return $this->hasMany(AttendanceCheckin::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}

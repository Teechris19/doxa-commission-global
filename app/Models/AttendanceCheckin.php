<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCheckin extends Model
{
    protected $fillable = [
        'attendance_session_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'source',
        'checked_in_at',
        'notes',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

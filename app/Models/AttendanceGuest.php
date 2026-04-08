<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_session_id',
        'name',
        'phone',
        'email',
        'status',
        'time',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'time' => 'datetime:H:i',
    ];

    // Relationships
    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Status constants
    const STATUS_PRESENT = 'present';
    const STATUS_LATE = 'late';
    const STATUS_ABSENT = 'absent';

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_LATE => 'Late',
            self::STATUS_ABSENT => 'Absent',
        ];
    }
}

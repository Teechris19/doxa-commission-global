<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'created_by',
        'session_type',
        'session_name',
        'service_id',
        'event_id',
        'location',
        'date',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function event()
    {
        return $this->belongsTo(Events::class);
    }

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeByChapter($query, $chapterId)
    {
        return $query->where('chapter_id', $chapterId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    // Accessors
    public function getRecordsCountAttribute()
    {
        return $this->records()->count();
    }

    public function getPresentCountAttribute()
    {
        return $this->records()->where('status', 'present')->count();
    }

    public function getAbsentCountAttribute()
    {
        return $this->records()->where('status', 'absent')->count();
    }

    public function getLateCountAttribute()
    {
        return $this->records()->where('status', 'late')->count();
    }
}

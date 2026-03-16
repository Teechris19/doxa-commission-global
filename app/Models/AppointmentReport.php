<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'appointment_reports';

    protected $fillable = [
        'appointment_id', 'title', 'summary', 'date', 'team_id', 'chapter_id', 'user_id', 'status', 'notes', 'report_file_path', 'chapter_id'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
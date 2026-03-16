<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'finance_id', 'title', 'summary', 'date', 'team_id', 'chapter_id', 'user_id', 'status', 'notes',  'chapter_id'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function finance()
    {
        return $this->belongsTo(Finance::class, );
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
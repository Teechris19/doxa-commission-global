<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Team;

class TeamFunction extends Model
{
    use HasFactory;

    protected $table = 'team_functions';

    protected $fillable = [
        'team_id',
        'function',
    ];

    protected $casts = [
        'function' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

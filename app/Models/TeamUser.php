<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamUser extends Model
{
    protected $table = 'team_user';

    protected $fillable = ['user_id', 'team_id', 'chapter_id', 'unit_id', 'role_in_team'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function leader()
    {
        return $this->hasOne(TeamUser::class)->where('role_in_team', 'team_lead');
    }
}

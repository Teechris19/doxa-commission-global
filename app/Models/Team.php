<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToChapter;

class Team extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'short', 'banner', 'has_team_lead', 'chapter_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role_in_team', 'unit_id')
            ->withTimestamps();
    }

    public function teamAssignments()
    {
        return $this->hasMany(TeamUser::class);
    }


    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function appointment(){
        return $this->hasOne(AppointmentTeams::class);
    }

    public function prayerRequests(){
        return $this->hasMany(PrayerRequestTeam::class);
    }

    public function believersAcademyTeam()
    {
        return $this->hasMany(BelieversAcademyTeams::class);
    }

    public function eventTeams()
    {
        return $this->hasMany(EventTeam::class);
    }

    public function leader(){
        return $this->hasOne(TeamUser::class)->where('role_in_team', 'team_lead');
    }

    public function teamFunction()
    {
        return $this->hasOne(TeamFunction::class, 'team_id');
    }
}

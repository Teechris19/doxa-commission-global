<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToChapter;

class Unit extends Model
{
    protected $fillable = ['name','short','team_id'];

    public function team() {
        return $this->belongsTo(Team::class);
    }

    public function users() {
        return $this->hasManyThrough(User::class, Team::class);
    }
}
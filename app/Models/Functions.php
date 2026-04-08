<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Functions extends Model
{
    protected $table = 'functions';

    protected $fillable = [
        'name',
        'description',
        'chapter_id'
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_functions', 'function_id', 'team_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function members(){
        return $this->hasMany(User::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function admin()
    {
        return $this->hasOne(User::class)->whereHas('roles', function($q){
            $q->where('name', 'admin');
        });
    }

    public function accounts()
    {
        return $this->hasMany(Accounts::class);
    }

    public function partnershipCategories()
    {
        return $this->hasMany(PartnershipCategory::class);
    }

    public function partnershipIntents()
    {
        return $this->hasMany(PartnershipIntent::class);
    }


}

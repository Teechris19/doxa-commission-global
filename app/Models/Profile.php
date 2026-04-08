<?php

namespace App\Models;

use App\Traits\FilterByChapter;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'phone',
        'secondary_phone',
        'email',
        'address',
        'city',
        'state',
        'country',
        'marital_status',
        'wedding_anniversary',
        'occupation',
        'employer',
        'education_level',
        'baptism_status',
        'membership_date',
        'avatar',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

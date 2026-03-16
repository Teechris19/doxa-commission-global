<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimony extends Model
{
    protected $fillable = [
        'name',
        'email',
        'testimony',
        'image',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

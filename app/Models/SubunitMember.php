<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubunitMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'subunit_id',
        'user_id',
    ];

    // Relationships
    public function subunit()
    {
        return $this->belongsTo(Subunit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

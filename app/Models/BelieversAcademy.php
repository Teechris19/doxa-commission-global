<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BelieversAcademy extends Model
{
    protected $table = 'believers_academies';

    public $fillable = [
        'status', 'chapter_id', 'start_at', 'certificate_template'
    ];

    public function classes()
    {
        return $this->hasMany(AcademyClases::class, 'academy_id');
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function students()
    {
        return $this->hasMany(StudentClasses::class, 'academy_id');
    }

    public function batches()
    {
        return $this->hasMany(AcademyBatch::class, 'academy_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyBatch extends Model
{
    protected $fillable = ['name', 'start_date', 'academy_id', 'max_students', 'status'];

    public function academy()
    {
        return $this->belongsTo(BeliversAcademy::class, 'academy_id');
    }

    public function students()
    {
        return $this->hasMany(StudentClasses::class, 'batch_id');
    }

    public function classes()
    {
        return $this->belongsToMany(AcademyClases::class, 'batch_classes', 'batch_id', 'class_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyClases extends Model
{
    public $fillable = ['name', 'description', 'date', 'time', 'study_material', 'tutor', 'academy_id'];

    public function academy()
    {
        return $this->belongsTo(BelieversAcademy::class, 'academy_id');
    }

    public function tutorUser()
    {
        return $this->belongsTo(User::class, 'tutor');
    }

    public function batches()
    {
        return $this->belongsToMany(AcademyBatch::class, 'batch_classes', 'class_id', 'batch_id');
    }
}

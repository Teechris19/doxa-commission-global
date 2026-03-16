<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClasses extends Model
{
    public $fillable = ['user_id', 'class_completed', 'status', 'cert', 'interest', 'how_did_you_know_about_us', 'phone', 'academy_id', 'batch_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academy()
    {
        return $this->belongsTo(BelieversAcademy::class, 'academy_id');
    }

    public function batch()
    {
        return $this->belongsTo(AcademyBatch::class, 'batch_id');
    }
}

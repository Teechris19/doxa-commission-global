<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SermonMedia extends Model
{
    protected $table = 'sermon_medias';

    protected $fillable = [
        'mediable_id',
        'mediable_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'type',
    ];

    /**
     * Get the parent mediable model (sermon).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventGallery extends Model
{
    use SoftDeletes;

    protected $table = 'event_galleries';

    protected $fillable = [
        'event_id',
        'chapter_id',
        'title',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'size',
        'order_column',
    ];

    protected $casts = [
        'size' => 'integer',
        'order_column' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Events::class, 'event_id');
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

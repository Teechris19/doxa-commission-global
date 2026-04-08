<?php

namespace App\Models;

use App\Models\Chapter;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;

class ChapterSetting extends Model
{
    protected $table = 'chapter_settings';

    protected $fillable = [
        'chapter_id',
        'name',
        'tagline',
        'logo',
        'banner_image',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'alt_phone',
        'email',
        'map_location',
        'livestream_url',
        'giving_url',
        'service_times',
        'special_events',
        'social_links',
        'extras',
    ];

    protected $casts = [
        'service_times' => 'array',
        'special_events' => 'array',
        'social_links' => 'array',
        'extras' => 'array',
    ];

    // Relationship to Chapter
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    // Relationship to services
    public function services()
    {
        return $this->hasMany(Service::class, 'chapter_id', 'chapter_id');
    }
}

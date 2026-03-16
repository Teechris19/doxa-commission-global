<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    protected $table = 'global_settings';

    protected $fillable = [
        'church_name',
        'denomination',
        'tagline',
        'logo',
        'favicon',
        'banner_image',
        'livestream_url',
        'podcast_url',
        'giving_url',
        'social_links',
        'footer_description',
        'footer_address',
        'footer_phone',
        'footer_email',
        'footer_services',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'extras',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'footer_services' => 'array',
        'extras' => 'array',
    ];
}

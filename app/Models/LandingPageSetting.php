<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageSetting extends Model
{
    protected $fillable = [
        'chapter_id',
        'navbar', 
        'carousel', 
        'sections',
        'services',
        'number_of_testimonies',
        'hero_section'
    ];

    protected $casts = [
        'carousel' => 'array',
        'services' => 'array',
        'hero_section' => 'array',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

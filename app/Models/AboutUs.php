<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutUs extends Model
{
    protected $table = 'about_us';

    protected $fillable = [
        'chapter_id',
        'title',
        'description',
        'mission',
        'vision',
        'core_values',
        'hero_image',
        'history_timeline',
        'is_active',
    ];

    protected $casts = [
        'history_timeline' => 'array',
        'is_active' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

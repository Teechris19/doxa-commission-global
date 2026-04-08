<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellPageSetting extends Model
{
    protected $fillable = [
        'chapter_id',
        'hero_title',
        'hero_subtitle',
        'hero_description',
        'hero_image',
        'hero_button_text',
        'left_heading',
        'right_description',
        'center_image',
        'cells_to_display',
        'faqs',
    ];

    protected $casts = [
        'faqs' => 'array',
        'cells_to_display' => 'integer',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastorSetting extends Model
{
    protected $fillable = [
        'chapter_id',
        'pastor_name',
        'pastor_title',
        'pastor_description',
        'pastor_image',
        'cta_button_text',
        'cta_button_url',
        'facebook_url',
        'instagram_url',
        'x_url',
        'youtube_url',
        'tiktok_url',
        'telegram_url',
        'whatsapp_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForChapter($query, $chapterId)
    {
        return $query->where('chapter_id', $chapterId)
            ->orWhereNull('chapter_id');
    }
}

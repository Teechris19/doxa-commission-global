<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pastor extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'name',
        'title',
        'description',
        'image',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'youtube_url',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

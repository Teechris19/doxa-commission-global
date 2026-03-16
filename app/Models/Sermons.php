<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sermons extends Model
{
    protected $fillable = [
        'title',
        'description',
        'preached_at',
        'image_path',
        'series_id',
    ];

    protected $casts = [
        'preached_at' => 'date',
    ];

    /**
     * Get the series that the sermon belongs to.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(SermonSeries::class, 'series_id');
    }

    /**
     * Get all of the sermon's media.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(SermonMedia::class, 'mediable');
    }
}

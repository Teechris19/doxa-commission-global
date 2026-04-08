<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SermonSeries extends Model
{
    protected $table = 'series';

    protected $fillable = [
        'title',
        'description',
        'image',
    ];

    /**
     * Get the sermons for the series.
     */
    public function sermons(): HasMany
    {
        return $this->hasMany(Sermons::class, 'series_id');
    }
}

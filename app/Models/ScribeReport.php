<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScribeReport extends Model
{
    protected $fillable = [
        'chapter_id',
        'created_by',
        'type',
        'title',
        'service_date',
        'content',
        'status',
    ];

    protected $casts = [
        'service_date' => 'date',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

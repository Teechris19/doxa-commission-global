<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyAsset extends Model
{
    protected $fillable = [
        'chapter_id',
        'name',
        'quantity',
        'purchase_date',
        'cost',
        'location',
        'condition',
        'low_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}

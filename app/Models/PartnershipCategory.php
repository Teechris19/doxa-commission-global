<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnershipCategory extends Model
{
    protected $fillable = [
        'chapter_id',
        'account_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function intents(): HasMany
    {
        return $this->hasMany(PartnershipIntent::class, 'partnership_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

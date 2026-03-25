<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'label',
        'name',
        'type',
        'options',
        'description',
        'placeholder',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'array',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function getOptionsArrayAttribute(): array
    {
        if (is_array($this->options)) {
            return $this->options;
        }

        if (is_string($this->options)) {
            $decoded = json_decode($this->options, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

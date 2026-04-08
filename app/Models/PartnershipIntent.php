<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnershipIntent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chapter_id',
        'user_id',
        'partnership_category_id',
        'account_id',
        'event_id',
        'intent_type',
        'title',
        'pledge_amount',
        'pledge_currency',
        'pledge_frequency',
        'status',
        'notes',
        'admin_notes',
        'pledged_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'pledge_amount' => 'decimal:2',
        'pledged_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartnershipCategory::class, 'partnership_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Events::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'reviewing', 'approved']);
    }
}


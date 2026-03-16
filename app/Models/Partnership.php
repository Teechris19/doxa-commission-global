<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partnership extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'preferred_location',
        'partnership_interests',
        'status',
        'notes',
        'organization',
        'website',
        'partnership_type',
        'proposed_amount',
        'start_date',
        'end_date',
        'assigned_to',
        'reviewed_at',
        'reviewed_by',
    ];
    
    protected $casts = [
        'proposed_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];
    
    protected $dates = [
        'start_date',
        'end_date',
        'reviewed_at',
        'deleted_at',
    ];
    
    // Relationships
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeByLocation($query, $location)
    {
        return $query->where('preferred_location', $location);
    }
    
    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'under_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'active' => 'primary',
        ];
        
        return $badges[$this->status] ?? 'secondary';
    }
}

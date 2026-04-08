<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accounts extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'account_name',
        'account_number',
        'bank_name',
        'bank_code',
        'swift_code',
        'iban',
        'account_type',
        'currency',
        'region',
        'country',
        'description',
        'is_active',
        'accepts_online_payments',
        'accepts_international',
        'supported_payment_methods',
        'minimum_amount',
        'maximum_amount',
        'special_instructions',
        'contact_person',
        'contact_email',
        'contact_phone',
        'chapter_id'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'accepts_online_payments' => 'boolean',
        'accepts_international' => 'boolean',
        'supported_payment_methods' => 'array',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
    ];
    
    protected $dates = [
        'deleted_at',
    ];
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }
    
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }
    
    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }
    
    public function scopeAcceptsOnlinePayments($query)
    {
        return $query->where('accepts_online_payments', true);
    }
    
    public function scopeAcceptsInternational($query)
    {
        return $query->where('accepts_international', true);
    }
    
    // Accessors
    public function getFormattedAccountNumberAttribute()
    {
        // Format account number for display (mask some digits for security)
        $accountNumber = $this->account_number;
        if (strlen($accountNumber) > 4) {
            return '****' . substr($accountNumber, -4);
        }
        return $accountNumber;
    }
    
    public function getDisplayNameAttribute()
    {
        return $this->account_name . ' (' . $this->bank_name . ')';
    }
    
    public function getRegionFlagAttribute()
    {
        $flags = [
            'North America' => '🇺🇸',
            'Europe' => '🇪🇺',
            'Asia-Pacific' => '🌏',
            'Latin America' => '🌎',
            'Middle East & Africa' => '🌍',
        ];
        
        return $flags[$this->region] ?? '🏦';
    }
    
    // Relationships
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Events::class, 'account_events', 'account_id', 'event_id');
    }

    public function partnershipIntents(): HasMany
    {
        return $this->hasMany(PartnershipIntent::class, 'account_id');
    }

    // Methods
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function supportsPaymentMethod($method)
    {
        return in_array($method, $this->supported_payment_methods ?? []);
    }

    public function isWithinAmountLimits($amount)
    {
        $withinMin = !$this->minimum_amount || $amount >= $this->minimum_amount;
        $withinMax = !$this->maximum_amount || $amount <= $this->maximum_amount;

        return $withinMin && $withinMax;
    }
}

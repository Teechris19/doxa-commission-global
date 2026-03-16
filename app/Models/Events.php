<?php

namespace App\Models;

use App\Traits\EventHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Events extends Model
{
    use SoftDeletes, EventHelper;

    protected $table = 'events';

    protected $fillable = [
        'chapter_id',
        'created_by',
        'title',
        'slug',
        'description',
        'start_at',
        'end_at',
        'timezone',
        'location',
        'is_online',
        'livestream_url',
        'banner',
        'status',
        'capacity',
        'registration_required',
        'form_schema',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_online' => 'boolean',
        'registration_required' => 'boolean',
        'form_schema' => 'array',
    ];

    // Relationships
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function forms()
    {
        return $this->hasMany(EventForm::class, 'event_id');
    }

    public function galleries()
    {
        return $this->hasMany(EventGallery::class, 'event_id');
    }

    public function accounts(){
        return $this->belongsToMany(Accounts::class, 'account_events', 'event_id', 'account_id');
    }

    public function eventTeams()
    {
        return $this->hasMany(EventTeam::class, 'event_id');
    }

    public function partnershipIntents()
    {
        return $this->hasMany(PartnershipIntent::class, 'event_id');
    }

    /**
     * Check if registration is currently open
     */
    public function isRegistrationOpen(): bool
    {
        // Must be published
        if ($this->status !== 'published') {
            return false;
        }

        // Must not have started yet
        if ($this->start_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if event has started
     */
    public function hasStarted(): bool
    {
        return $this->start_at->isPast();
    }

    /**
     * Check if event has ended
     */
    public function hasEnded(): bool
    {
        if (!$this->end_at) {
            return false;
        }
        return $this->end_at->isPast();
    }

    /**
     * Get remaining capacity
     */
    public function getRemainingCapacity(): ?int
    {
        if (!$this->capacity) {
            return null;
        }

        $registered = $this->accounts()->count();
        return max(0, $this->capacity - $registered);
    }

    /**
     * Check if event is at capacity
     */
    public function isAtCapacity(): bool
    {
        if (!$this->capacity) {
            return false;
        }

        $registered = $this->accounts()->count();
        return $registered >= $this->capacity;
    }
}

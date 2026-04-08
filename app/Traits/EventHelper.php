<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait EventHelper
{
    /**
     * Get event status text
     */
    public function getEventStatusText(): string
    {
        if (!$this->status || $this->status !== 'published') {
            return 'Not Published';
        }

        if ($this->hasEnded()) {
            return 'Ended';
        }

        if ($this->hasStarted()) {
            return 'Ongoing';
        }

        return 'Upcoming';
    }

    /**
     * Get event status badge color
     */
    public function getEventStatusBadgeClass(): string
    {
        if (!$this->status || $this->status !== 'published') {
            return 'badge-secondary';
        }

        if ($this->hasEnded()) {
            return 'badge-dark';
        }

        if ($this->hasStarted()) {
            return 'badge-success';
        }

        return 'badge-primary';
    }

    /**
     * Get time until event starts
     */
    public function getTimeUntilStart(): ?string
    {
        if ($this->hasStarted()) {
            return null;
        }

        return $this->start_at->diffForHumans();
    }

    /**
     * Get time until event ends
     */
    public function getTimeUntilEnd(): ?string
    {
        if ($this->hasEnded()) {
            return null;
        }

        if (!$this->end_at) {
            return null;
        }

        return $this->end_at->diffForHumans();
    }

    /**
     * Get registration status text
     */
    public function getRegistrationStatusText(): string
    {
        if (!$this->registration_required) {
            return 'Registration not required';
        }

        if ($this->isAtCapacity()) {
            return 'Event is full';
        }

        if ($this->isRegistrationOpen()) {
            return 'Registration open';
        }

        if ($this->hasStarted()) {
            return 'Registration closed';
        }

        return 'Registration not open yet';
    }

    /**
     * Get registration status badge class
     */
    public function getRegistrationStatusBadgeClass(): string
    {
        if (!$this->registration_required) {
            return 'badge-info';
        }

        if ($this->isAtCapacity()) {
            return 'badge-danger';
        }

        if ($this->isRegistrationOpen()) {
            return 'badge-success';
        }

        return 'badge-warning';
    }
}

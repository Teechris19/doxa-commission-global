<?php

namespace App\Policies;

use App\Models\PartnershipFormField;
use App\Models\User;

class PartnershipFormFieldPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'team-lead']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PartnershipFormField $partnershipFormField): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'team-lead'])
            || $partnershipFormField->chapter_id === $user->chapter_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'team-lead']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PartnershipFormField $partnershipFormField): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'team-lead'])
            && ($partnershipFormField->chapter_id === $user->chapter_id || is_null($partnershipFormField->chapter_id));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PartnershipFormField $partnershipFormField): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'team-lead'])
            && ($partnershipFormField->chapter_id === $user->chapter_id || is_null($partnershipFormField->chapter_id));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PartnershipFormField $partnershipFormField): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PartnershipFormField $partnershipFormField): bool
    {
        return $user->hasRole('super-admin');
    }
}

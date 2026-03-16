<?php

namespace App\Policies;

use App\Models\PartnershipIntent;
use App\Models\User;

class PartnershipIntentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasGlobalPartnershipAccess($user) || $this->isPartnershipTeamMember($user);
    }

    public function view(User $user, PartnershipIntent $intent): bool
    {
        return $this->canManageChapterPartnership($user, (int) $intent->chapter_id);
    }

    public function create(User $user, int $chapterId): bool
    {
        return $this->canManageChapterPartnership($user, $chapterId);
    }

    public function update(User $user, PartnershipIntent $intent): bool
    {
        return $this->canManageChapterPartnership($user, (int) $intent->chapter_id);
    }

    public function delete(User $user, PartnershipIntent $intent): bool
    {
        return $this->canManageChapterPartnership($user, (int) $intent->chapter_id);
    }

    protected function canManageChapterPartnership(User $user, int $chapterId): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ((int) $user->chapter_id !== $chapterId) {
            return false;
        }

        return $this->hasGlobalPartnershipAccess($user) || $this->isPartnershipTeamMember($user, $chapterId);
    }

    protected function hasGlobalPartnershipAccess(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    protected function isPartnershipTeamMember(User $user, ?int $chapterId = null): bool
    {
        $teams = $user->teams();

        if ($chapterId) {
            $teams->where('chapter_id', $chapterId);
        }

        return $teams
            ->whereRaw('LOWER(name) LIKE ?', ['%partnership%'])
            ->exists();
    }
}


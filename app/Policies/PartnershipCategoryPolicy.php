<?php

namespace App\Policies;

use App\Models\PartnershipCategory;
use App\Models\TeamFunction;
use App\Models\User;

class PartnershipCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasGlobalPartnershipAccess($user) || $this->isPartnershipTeamMember($user);
    }

    public function view(User $user, PartnershipCategory $category): bool
    {
        return $this->canManageChapterPartnership($user, (int) $category->chapter_id);
    }

    public function create(User $user, int $chapterId): bool
    {
        return $this->canManageChapterPartnership($user, $chapterId);
    }

    public function update(User $user, PartnershipCategory $category): bool
    {
        return $this->canManageChapterPartnership($user, (int) $category->chapter_id);
    }

    public function delete(User $user, PartnershipCategory $category): bool
    {
        return $this->canManageChapterPartnership($user, (int) $category->chapter_id);
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
        $userTeamIds = $user->teams()
            ->when($chapterId, fn($q) => $q->where('teams.chapter_id', $chapterId))
            ->pluck('teams.id');

        if ($userTeamIds->isEmpty()) {
            return false;
        }

        return TeamFunction::whereIn('team_id', $userTeamIds)
            ->get()
            ->contains(fn($tf) => !empty($tf->function['partnerships']));
    }
}


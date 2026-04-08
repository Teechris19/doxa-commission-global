<?php

namespace App\Policies;

use App\Models\User;
use App\Models\User as Member;
use Illuminate\Auth\Access\Response;

class MemberPolicy
{
    /**
     * Determine if the user can view any members.
     */
    public function viewAny(User $user)
    {
        // Super admin can view all members
        if ($user->hasRole('super-admin')) {
            return Response::allow();
        }

        // Admin can view members in their chapter
        if ($user->hasRole('admin')) {
            return Response::allow();
        }

        // Team lead can view members in their teams
        if ($user->hasRole('team-lead')) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view members.');
    }

    /**
     * Determine if the user can view the member.
     */
    public function view(User $user, Member $member)
    {
        // Super admin can view any member
        if ($user->hasRole('super-admin')) {
            return Response::allow();
        }

        // Admin can view members in their chapter
        if ($user->hasRole('admin') && $user->chapter_id === $member->chapter_id) {
            return Response::allow();
        }

        // Team lead can view members in their teams
        if ($user->hasRole('team-lead')) {
            $teamIds = $user->teams
                ->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead']))
                ->pluck('id');
            
            if ($member->teams->contains('id', $teamIds)) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to view this member.');
    }

    /**
     * Determine if the user can create members.
     */
    public function create(User $user)
    {
        // Super admin, admin, and team leads can create members
        return $user->hasRole(['super-admin', 'admin', 'team-lead', 'lead-assist', 'lead_assist'])
            ? Response::allow()
            : Response::deny('You do not have permission to create members.');
    }

    /**
     * Determine if the user can update the member.
     */
    public function update(User $user, Member $member)
    {
        // Super admin can update any member
        if ($user->hasRole('super-admin')) {
            return Response::allow();
        }

        // Admin can update members in their chapter
        if ($user->hasRole('admin') && $user->chapter_id === $member->chapter_id) {
            return Response::allow();
        }

        // Team lead can update members in their teams
        if ($user->hasRole('team-lead')) {
            $teamIds = $user->teams
                ->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead']))
                ->pluck('id');
            
            if ($member->teams->contains('id', $teamIds)) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to update this member.');
    }

    /**
     * Determine if the user can delete the member.
     */
    public function delete(User $user, Member $member)
    {
        // Only super admin can delete members
        return $user->hasRole('super-admin')
            ? Response::allow()
            : Response::deny('You do not have permission to delete members.');
    }

    /**
     * Determine if the user can restore the member.
     */
    public function restore(User $user, Member $member)
    {
        // Only super admin can restore members
        return $user->hasRole('super-admin')
            ? Response::allow()
            : Response::deny('You do not have permission to restore members.');
    }

    /**
     * Determine if the user can permanently delete the member.
     */
    public function forceDelete(User $user, Member $member)
    {
        // Only super admin can permanently delete members
        return $user->hasRole('super-admin')
            ? Response::allow()
            : Response::deny('You do not have permission to permanently delete members.');
    }

    /**
     * Determine if the user can manage team assignments for the member.
     */
    public function manageTeams(User $user, Member $member)
    {
        // Super admin can manage teams for any member
        if ($user->hasRole('super-admin')) {
            return Response::allow();
        }

        // Admin can manage teams for members in their chapter
        if ($user->hasRole('admin') && $user->chapter_id === $member->chapter_id) {
            return Response::allow();
        }

        // Team lead can manage teams for members in their teams
        if ($user->hasRole('team-lead')) {
            $teamIds = $user->teams
                ->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead']))
                ->pluck('id');
            
            if ($member->teams->contains('id', $teamIds)) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to manage team assignments.');
    }

    /**
     * Determine if the user can export member data.
     */
    public function export(User $user)
    {
        // Super admin and admin can export member data
        return $user->hasRole(['super-admin', 'admin'])
            ? Response::allow()
            : Response::deny('You do not have permission to export member data.');
    }

    /**
     * Determine if the user can view sensitive member information.
     */
    public function viewSensitive(User $user, Member $member)
    {
        // Super admin can view all sensitive information
        if ($user->hasRole('super-admin')) {
            return Response::allow();
        }

        // Admin can view sensitive information for members in their chapter
        if ($user->hasRole('admin') && $user->chapter_id === $member->chapter_id) {
            return Response::allow();
        }

        // Team lead can view sensitive information for members in their teams
        if ($user->hasRole('team-lead')) {
            $teamIds = $user->teams
                ->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead']))
                ->pluck('id');
            
            if ($member->teams->contains('id', $teamIds)) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to view sensitive member information.');
    }
}
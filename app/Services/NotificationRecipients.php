<?php

namespace App\Services;

use App\Models\TeamUser;
use App\Models\User;
use App\Models\TeamFunction;
use App\Models\Team;
use App\Models\AppointmentTeams;
use App\Models\PrayerRequestTeam;
use App\Models\BelieversAcademyTeams;
use App\Models\EventTeam;
use Illuminate\Support\Collection;

class NotificationRecipients
{
    public function adminsForChapter(?int $chapterId): Collection
    {
        $admins = User::role(['admin'])
            ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
            ->get();

        $superAdmins = User::role(['super-admin'])->get();

        return $admins->merge($superAdmins)->unique('id');
    }

    public function teamLeadsForTeam(int $teamId): Collection
    {
        return TeamUser::where('team_id', $teamId)
            ->whereIn('role_in_team', ['team-lead', 'lead-assist', 'lead_assist'])
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id');
    }

    public function forTeamAndChapter(int $teamId, ?int $chapterId): Collection
    {
        return $this->teamLeadsForTeam($teamId)
            ->merge($this->adminsForChapter($chapterId))
            ->unique('id');
    }

    public function teamLeadsForFunction(string $functionKey, ?int $chapterId): Collection
    {
        $relationMap = [
            'appointments' => AppointmentTeams::class,
            'prayer_requests' => PrayerRequestTeam::class,
            'believers_academy' => BelieversAcademyTeams::class,
            'events' => EventTeam::class,
        ];

        $teamIds = collect();

        if (array_key_exists($functionKey, $relationMap)) {
            $model = $relationMap[$functionKey];
            $teamIds = $model::query()
                ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
                ->pluck('team_id')
                ->unique()
                ->values();
        } else {
            $teamIds = TeamFunction::query()
                ->where("function->{$functionKey}", true)
                ->whereHas('team', fn($q) => $q->when($chapterId, fn($qq) => $qq->where('chapter_id', $chapterId)))
                ->pluck('team_id')
                ->unique()
                ->values();
        }

        $recipients = collect();
        foreach ($teamIds as $teamId) {
            $recipients = $recipients->merge($this->teamLeadsForTeam((int) $teamId));
        }

        return $recipients->unique('id');
    }

    public function forFunctionAndChapter(string $functionKey, ?int $chapterId): Collection
    {
        return $this->teamLeadsForFunction($functionKey, $chapterId)
            ->merge($this->adminsForChapter($chapterId))
            ->unique('id');
    }
}

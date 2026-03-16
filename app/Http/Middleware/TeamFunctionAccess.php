<?php

namespace App\Http\Middleware;

use App\Models\AppointmentTeams;
use App\Models\BelieversAcademyTeams;
use App\Models\EventTeam;
use App\Models\PrayerRequestTeam;
use App\Models\TeamFunction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TeamFunctionAccess
{
    public function handle(Request $request, Closure $next, string $functionKey): Response
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'UNAUTHORIZED');
        }

        if ($user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }

        if (!$user->hasRole(['team-lead', 'lead-assist', 'lead_assist'])) {
            return $next($request);
        }

        $leadersTeam = $user->teams->firstWhere(
            fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist'])
        );

        if (!$leadersTeam) {
            abort(403, 'ACCESS DENIED');
        }

        $chapterId = $user->chapter_id;

        $relationMap = [
            'appointments' => AppointmentTeams::class,
            'prayer_requests' => PrayerRequestTeam::class,
            'believers_academy' => BelieversAcademyTeams::class,
            'events' => EventTeam::class,
        ];

        if (array_key_exists($functionKey, $relationMap)) {
            $model = $relationMap[$functionKey];
            $assigned = $model::where('chapter_id', $chapterId)
                ->where('team_id', $leadersTeam->id)
                ->exists();

            if (!$assigned) {
                abort(403, 'ACCESS DENIED');
            }

            return $next($request);
        }

        $teamFunctions = TeamFunction::where('team_id', $leadersTeam->id)->first();
        $functionMap = $teamFunctions?->function ?? [];

        if (empty($functionMap[$functionKey])) {
            abort(403, 'ACCESS DENIED');
        }

        return $next($request);
    }
}

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

        // Load teams with pivot data explicitly
        $user->load('teams');
        
        $leadersTeam = $user->teams->firstWhere(
            fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist'])
        );

        if (!$leadersTeam) {
            abort(403, 'ACCESS DENIED - You are not a team lead');
        }

        $chapterId = $user->chapter_id;

        $relationMap = [
            'appointments' => AppointmentTeams::class,
            'prayer_requests' => PrayerRequestTeam::class,
            'believers_academy' => BelieversAcademyTeams::class,
            'events' => EventTeam::class,
        ];

        // Members is always accessible to team leaders (they manage their own team members)
        if ($functionKey === 'members') {
            return $next($request);
        }

        if (array_key_exists($functionKey, $relationMap)) {
            $model = $relationMap[$functionKey];
            $assigned = $model::where('chapter_id', $chapterId)
                ->where('team_id', $leadersTeam->id)
                ->exists();

            if (!$assigned) {
                abort(403, 'ACCESS DENIED - Team not assigned to this function');
            }

            return $next($request);
        }

        // Load team functions for remaining checks
        $teamFunctions = TeamFunction::where('team_id', $leadersTeam->id)->first();
        $functionMap = $teamFunctions?->function ?? [];

        // Check for partnership function - uses team_functions table
        if ($functionKey === 'partnerships') {
            if (empty($functionMap['partnerships'])) {
                abort(403, 'Your team is not assigned to handle partnerships. Please contact your administrator.');
            }
            return $next($request);
        }

        // Check if the function key exists in the team's function map
        if (empty($functionMap[$functionKey])) {
            abort(403, 'Your team is not assigned to this function. Please contact your administrator.');
        }

        return $next($request);
    }
}

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\TeamFunction;

echo "=== Debug Middleware Check ===" . PHP_EOL . PHP_EOL;

$user = User::where('email', 'tee@gmail.com')->first();

echo "User: {$user->name}" . PHP_EOL;
echo "Has team-lead role: " . ($user->hasRole('team-lead') ? 'YES' : 'NO') . PHP_EOL . PHP_EOL;

// This is exactly what the middleware does
$leadersTeam = $user->teams->firstWhere(
    fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist'])
);

if (!$leadersTeam) {
    echo "✗ NO TEAM FOUND where user is team-lead" . PHP_EOL;
    echo "   This is the problem!" . PHP_EOL;
    exit(1);
}

echo "✓ Leader's Team: {$leadersTeam->name} (ID: {$leadersTeam->id})" . PHP_EOL;
echo "  Role in team: {$leadersTeam->pivot->role_in_team}" . PHP_EOL . PHP_EOL;

// Check team_functions for THIS specific team
$teamFunctions = TeamFunction::where('team_id', $leadersTeam->id)->first();

if (!$teamFunctions) {
    echo "✗ NO team_functions record for team ID {$leadersTeam->id}" . PHP_EOL;
    exit(1);
}

echo "Team Functions: " . json_encode($teamFunctions->function) . PHP_EOL;
echo "Has partnerships: " . (isset($teamFunctions->function['partnerships']) && $teamFunctions->function['partnerships'] ? 'YES ✓' : 'NO ✗') . PHP_EOL . PHP_EOL;

if (isset($teamFunctions->function['partnerships']) && $teamFunctions->function['partnerships']) {
    echo "=== MIDDLEWARE SHOULD ALLOW ACCESS ===" . PHP_EOL;
} else {
    echo "=== MIDDLEWARE WILL DENY ACCESS ===" . PHP_EOL;
}

echo PHP_EOL;

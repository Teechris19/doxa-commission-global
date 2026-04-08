<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Models\TeamUser;

echo "=== FINANCE Team Leaders ===" . PHP_EOL . PHP_EOL;

$financeTeam = Team::find(65);
if (!$financeTeam) {
    echo "FINANCE team not found!" . PHP_EOL;
    exit(1);
}

echo "Team: {$financeTeam->name}" . PHP_EOL;
echo "Chapter: " . ($financeTeam->chapter?->name ?? 'N/A') . PHP_EOL . PHP_EOL;

// Get all users who are team leads of FINANCE team
$teamUserRecords = TeamUser::where('team_id', 65)
    ->where('role_in_team', 'team-lead')
    ->with('user')
    ->get();

if ($teamUserRecords->isEmpty()) {
    echo "NO TEAM LEADS assigned to FINANCE team!" . PHP_EOL . PHP_EOL;
    echo "To add a team lead:" . PHP_EOL;
    echo "1. Go to: http://localhost:8000/admin/dashboard/teams" . PHP_EOL;
    echo "2. Edit the FINANCE team" . PHP_EOL;
    echo "3. Assign a team lead" . PHP_EOL;
} else {
    echo "Team Leads who can access partnerships:" . PHP_EOL;
    foreach ($teamUserRecords as $record) {
        echo "  - {$record->user->name} ({$record->user->email})" . PHP_EOL;
    }
}

echo PHP_EOL;

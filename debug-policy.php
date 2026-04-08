<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\TeamFunction;

echo "=== Debug Partnership Policy Check ===" . PHP_EOL . PHP_EOL;

$user = User::where('email', 'tee@gmail.com')->first();

if (!$user) {
    echo "User not found!" . PHP_EOL;
    exit(1);
}

echo "User: {$user->name}" . PHP_EOL;
echo "Chapter ID: {$user->chapter_id}" . PHP_EOL;
echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . PHP_EOL . PHP_EOL;

// Check viewAny permission
echo "=== Checking viewAny Permission ===" . PHP_EOL;

// Check if admin/super-admin
if ($user->hasRole(['super-admin', 'admin'])) {
    echo "✓ User is admin/super-admin - ACCESS GRANTED" . PHP_EOL;
    exit(0);
}
echo "✗ User is NOT admin/super-admin" . PHP_EOL;

// Get user's team IDs
$userTeamIds = $user->teams()->pluck('teams.id');
echo "User's team IDs: " . $userTeamIds->implode(', ') . PHP_EOL;

if ($userTeamIds->isEmpty()) {
    echo "✗ User is not in any teams - ACCESS DENIED" . PHP_EOL;
    exit(1);
}

// Check team_functions
$teamFunctions = TeamFunction::whereIn('team_id', $userTeamIds)->get();
echo PHP_EOL . "Team Functions records found: " . $teamFunctions->count() . PHP_EOL;

foreach ($teamFunctions as $tf) {
    echo "  Team ID {$tf->team_id}: " . json_encode($tf->function) . PHP_EOL;
}

$hasPartnerships = $teamFunctions->contains(fn($tf) => !empty($tf->function['partnerships']));

echo PHP_EOL;
if ($hasPartnerships) {
    echo "✓ FOUND team with partnerships access - ACCESS GRANTED" . PHP_EOL;
} else {
    echo "✗ NO team has partnerships access - ACCESS DENIED" . PHP_EOL;
}

echo PHP_EOL;

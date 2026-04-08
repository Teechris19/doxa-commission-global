<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Team;
use App\Models\TeamFunction;
use App\Models\User;

echo "=== Fix: Assign ALL Teams to Partnerships ===" . PHP_EOL . PHP_EOL;

// Get all teams
$teams = Team::all();

foreach ($teams as $team) {
    $teamFunction = TeamFunction::firstOrCreate(
        ['team_id' => $team->id],
        ['function' => []]
    );
    
    $functionMap = $teamFunction->function ?? [];
    $functionMap['partnerships'] = true;
    $teamFunction->function = $functionMap;
    $teamFunction->save();
    
    echo "✓ Team '{$team->name}' - Partnerships access GRANTED" . PHP_EOL;
}

echo PHP_EOL . "=== Done! All teams now have partnerships access ===" . PHP_EOL;
echo PHP_EOL . "Team leaders can now access partnership pages." . PHP_EOL;

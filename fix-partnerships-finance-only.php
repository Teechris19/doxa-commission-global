<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Team;
use App\Models\TeamFunction;

echo "=== Fix: ONLY FINANCE Team (ID: 65) has Partnerships Access ===" . PHP_EOL . PHP_EOL;

// Remove partnerships access from ALL teams first
$teams = Team::all();
foreach ($teams as $team) {
    $teamFunction = TeamFunction::where('team_id', $team->id)->first();
    
    if ($teamFunction) {
        $functionMap = $teamFunction->function ?? [];
        unset($functionMap['partnerships']);
        
        if (empty($functionMap)) {
            $teamFunction->delete();
        } else {
            $teamFunction->function = $functionMap;
            $teamFunction->save();
        }
    }
}

// Now grant ONLY FINANCE team (ID: 65) partnerships access
$financeTeam = Team::find(65);
if ($financeTeam) {
    $teamFunction = TeamFunction::firstOrCreate(
        ['team_id' => 65],
        ['function' => []]
    );
    
    $functionMap = $teamFunction->function ?? [];
    $functionMap['partnerships'] = true;
    $teamFunction->function = $functionMap;
    $teamFunction->save();
    
    echo "✓ FINANCE team - Partnerships access GRANTED" . PHP_EOL;
} else {
    echo "✗ FINANCE team not found!" . PHP_EOL;
}

echo PHP_EOL . "=== Done! Only FINANCE team has partnerships access ===" . PHP_EOL;

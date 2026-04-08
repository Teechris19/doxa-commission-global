<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TeamFunction;

echo "=== Checking team_functions table ===" . PHP_EOL . PHP_EOL;

$allFunctions = TeamFunction::all();

if ($allFunctions->isEmpty()) {
    echo "No records in team_functions table!" . PHP_EOL;
} else {
    foreach ($allFunctions as $tf) {
        echo "Team ID: {$tf->team_id}" . PHP_EOL;
        echo "Function data: " . json_encode($tf->function) . PHP_EOL;
        echo "Has partnerships: " . (isset($tf->function['partnerships']) && $tf->function['partnerships'] ? 'YES' : 'NO') . PHP_EOL;
        echo "---" . PHP_EOL;
    }
}

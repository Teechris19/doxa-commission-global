<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Team;
use App\Models\Chapter;
use App\Models\TeamFunction;

echo "=== Available Chapters ===" . PHP_EOL;
Chapter::all(['id', 'name'])->each(function($c) {
    echo "{$c->id}: {$c->name}" . PHP_EOL;
});

echo PHP_EOL . "Enter Chapter ID: ";
$chapterId = trim(fgets(STDIN));

echo PHP_EOL . "=== Teams in this Chapter ===" . PHP_EOL;
Team::where('chapter_id', $chapterId)->get(['id', 'name'])->each(function($t) {
    echo "{$t->id}: {$t->name}" . PHP_EOL;
});

echo PHP_EOL . "Enter Team ID to assign to Partnerships: ";
$teamId = trim(fgets(STDIN));

$team = Team::find($teamId);
if (!$team) {
    echo "Invalid Team ID!" . PHP_EOL;
    exit(1);
}

$teamFunction = TeamFunction::firstOrCreate(
    ['team_id' => $teamId],
    ['function' => []]
);

$functionMap = $teamFunction->function ?? [];
$functionMap['partnerships'] = true;
$teamFunction->function = $functionMap;
$teamFunction->save();

echo PHP_EOL . "✓ Team '{$team->name}' has been assigned to handle Partnerships!" . PHP_EOL;
echo PHP_EOL . "Current functions for this team:" . PHP_EOL;
foreach ($functionMap as $key => $value) {
    if ($value) echo "  - {$key}" . PHP_EOL;
}

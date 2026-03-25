<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Team;
use App\Models\TeamFunction;

echo "=== All Teams ===" . PHP_EOL;
printf("%-5s | %-30s | %-10s" . PHP_EOL, "ID", "Name", "Chapter");
echo str_repeat("-", 50) . PHP_EOL;

Team::with('chapter')->orderBy('chapter_id')->orderBy('name')->get()
    ->each(function($t) {
        printf("%-5s | %-30s | %-10s" . PHP_EOL, 
            $t->id, 
            substr($t->name, 0, 30),
            $t->chapter?->name ?? 'N/A'
        );
    });

echo PHP_EOL . "=== Teams with Partnerships Access ===" . PHP_EOL;
$teamsWithAccess = Team::whereHas('teamFunction', function($q) {
    $q->whereJsonContains('function->partnerships', true);
})->with('chapter')->get();

if ($teamsWithAccess->isEmpty()) {
    echo "No teams currently have partnerships access." . PHP_EOL;
} else {
    $teamsWithAccess->each(function($t) {
        printf("%-5s | %-30s | %-10s" . PHP_EOL, 
            $t->id, 
            substr($t->name, 0, 30),
            $t->chapter?->name ?? 'N/A'
        );
    });
}

echo PHP_EOL;

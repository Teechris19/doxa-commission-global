<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Check User: tee@gmail.com ===" . PHP_EOL . PHP_EOL;

$user = User::where('email', 'tee@gmail.com')->first();

if (!$user) {
    echo "User not found!" . PHP_EOL;
    exit(1);
}

echo "Name: {$user->name}" . PHP_EOL;
echo "Email: {$user->email}" . PHP_EOL;
echo "Chapter ID: {$user->chapter_id}" . PHP_EOL . PHP_EOL;

echo "Roles: " . PHP_EOL;
$roles = $user->getRoleNames();
foreach ($roles as $role) {
    echo "  - {$role}" . PHP_EOL;
}

echo PHP_EOL . "Teams: " . PHP_EOL;
$userTeams = $user->teams()->withPivot('role_in_team')->get();
foreach ($userTeams as $team) {
    echo "  - {$team->name} (Role: {$team->pivot->role_in_team})" . PHP_EOL;
}

echo PHP_EOL;

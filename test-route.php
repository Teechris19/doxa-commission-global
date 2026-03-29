<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if route exists
$routes = \Route::getRoutes();

echo "=== Routes containing 'members' ===" . PHP_EOL;
foreach ($routes as $route) {
    if (strpos($route->uri, 'members') !== false) {
        echo $route->uri . ' => ' . ($route->getName() ?? 'no name') . PHP_EOL;
    }
}

echo PHP_EOL . "=== Looking for add-to-team specifically ===" . PHP_EOL;
$route = \Route::getRoutes()->getByName('admin.members.add-to-team');
if ($route) {
    echo "FOUND: " . $route->uri . PHP_EOL;
} else {
    echo "NOT FOUND - Route 'admin.members.add-to-team' does not exist" . PHP_EOL;
}

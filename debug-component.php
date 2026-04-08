<?php

// Quick debug script to test the component
require __DIR__.'/vendor/autoload.php';

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
    $kernel->bootstrap();
    
    // Try to resolve the component
    echo "Testing component resolution..." . PHP_EOL;
    
    // Check if Livewire Volt is installed
    if (class_exists('Livewire\Volt\Volt')) {
        echo "Livewire Volt is installed" . PHP_EOL;
    } else {
        echo "Livewire Volt NOT found" . PHP_EOL;
    }
    
    // List all registered routes for admin
    $routes = \Route::getRoutes();
    echo PHP_EOL . "All admin/dashboard routes:" . PHP_EOL;
    foreach ($routes as $route) {
        if (strpos($route->uri, 'admin/dashboard/members') !== false) {
            echo "  " . $route->uri . " => " . ($route->getName() ?? 'no name') . PHP_EOL;
        }
    }
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

<?php

// Script to fix the attendance migrations
// Run this with: php fix-attendance-migrations.php

echo "Checking attendance tables...\n\n";

// Load Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['attendance_sessions', 'attendance_records'];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' does NOT exist\n";
    }
}

echo "\n";
echo "=================================================\n";
echo "NEXT STEP:\n";
echo "=================================================\n";
echo "In your terminal, run:\n";
echo "\n";
echo "  php artisan migrate\n";
echo "\n";
echo "The migrations are now set to skip if tables exist.\n";
echo "=================================================\n";

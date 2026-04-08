<?php

// Check attendance_sessions table structure
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking attendance_sessions table structure...\n\n";

if (!Schema::hasTable('attendance_sessions')) {
    echo "Table does NOT exist\n";
    exit;
}

echo "Table exists!\n\n";

$columns = Schema::getColumnListing('attendance_sessions');
echo "Columns:\n";
foreach ($columns as $column) {
    echo "  - $column\n";
}

echo "\n";

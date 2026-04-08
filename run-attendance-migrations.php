<?php

// Simple script to run migrations
// Execute this by running: php run-migrations.php

echo "Running Attendance System Migrations...\n\n";

$migrations = [
    'database/migrations/2026_03_31_000001_create_subunits_table.php',
    'database/migrations/2026_03_31_000002_create_subunit_members_table.php',
    'database/migrations/2026_03_31_000003_create_attendance_sessions_table.php',
    'database/migrations/2026_03_31_000004_create_attendance_records_table.php',
];

foreach ($migrations as $migration) {
    if (file_exists($migration)) {
        echo "✓ Found: $migration\n";
    } else {
        echo "✗ Missing: $migration\n";
    }
}

echo "\n";
echo "To run the migrations, execute this command in your terminal:\n";
echo "--------------------------------------------------------------\n";
echo "cd C:\\Users\\USER\\Desktop\\DCG\n";
echo "php artisan migrate\n";
echo "--------------------------------------------------------------\n";

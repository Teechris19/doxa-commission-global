<?php
// Fix for about_us table - run this by visiting the URL
// URL: http://localhost:8000/fix-about-us-table.php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h1>Fixing about_us Table...</h1>";

try {
    DB::statement('ALTER TABLE `about_us` MODIFY `title` VARCHAR(255) NULL');
    DB::statement('ALTER TABLE `about_us` MODIFY `description` TEXT NULL');
    echo "<h2 style='color:green'>✅ DONE! Now delete this file.</h2>";
    echo "<a href='/admin/dashboard/settings/about-page'>Go to About Settings</a>";
} catch (\Exception $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}

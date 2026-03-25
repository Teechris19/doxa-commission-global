<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PartnershipFormField;

echo "=== Clearing All Partnership Form Fields ===" . PHP_EOL . PHP_EOL;

$count = PartnershipFormField::count();
PartnershipFormField::truncate();

echo "Deleted {$count} form fields." . PHP_EOL;
echo "Form fields table is now empty." . PHP_EOL;
echo PHP_EOL . "Admins can now create custom fields from scratch." . PHP_EOL;

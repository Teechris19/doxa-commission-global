<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PartnershipFormField;
use App\Models\Chapter;

echo "=== Partnership Form Fields ===" . PHP_EOL . PHP_EOL;

echo "Total fields: " . PartnershipFormField::count() . PHP_EOL . PHP_EOL;

echo "Fields by chapter:" . PHP_EOL;
PartnershipFormField::all()->each(function($f) {
    $chapter = $f->chapter_id ? Chapter::find($f->chapter_id)?->name : 'GLOBAL';
    printf("ID: %-3s | Chapter: %-20s | Label: %-20s | Active: %s" . PHP_EOL, 
        $f->id, 
        $chapter ?? 'N/A',
        $f->label,
        $f->is_active ? 'YES' : 'NO'
    );
});

echo PHP_EOL . "=== Calabar Branch (Chapter ID: 1) Fields ===" . PHP_EOL;
$calabarFields = PartnershipFormField::where('chapter_id', 1)->get();
if ($calabarFields->isEmpty()) {
    echo "No fields found for Calabar Branch!" . PHP_EOL;
} else {
    echo "Found " . $calabarFields->count() . " fields" . PHP_EOL;
}

echo PHP_EOL . "=== Global Fields (NULL chapter_id) ===" . PHP_EOL;
$globalFields = PartnershipFormField::whereNull('chapter_id')->get();
if ($globalFields->isEmpty()) {
    echo "No global fields found!" . PHP_EOL;
} else {
    echo "Found " . $globalFields->count() . " fields" . PHP_EOL;
}

<?php
// Debug page for About Us content
// Visit: http://localhost:8000/debug-about-us

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\{AboutUs, Chapter};

echo "<h1>About Us Debug</h1>";

// Get all chapters
$chapters = Chapter::all();
echo "<h2>Chapters:</h2><ul>";
foreach ($chapters as $c) {
    echo "<li>{$c->name} (ID: {$c->id})</li>";
}
echo "</ul>";

// Get all AboutUs records
$aboutRecords = AboutUs::all();
echo "<h2>AboutUs Records:</h2><ul>";
foreach ($aboutRecords as $a) {
    echo "<li>";
    echo "Chapter ID: {$a->chapter_id} | ";
    echo "Hero Title: " . ($a->hero_title ?? 'NULL') . " | ";
    echo "Hero Subtitle: " . ($a->hero_subtitle ?? 'NULL') . " | ";
    echo "Description: " . (substr($a->description ?? '', 0, 50)) . "... | ";
    echo "Active: " . $a->is_active;
    echo "</li>";
}
echo "</ul>";

// Check specific chapter
$calabar = Chapter::where('name', 'LIKE', '%Calabar%')->first();
if ($calabar) {
    echo "<h2>Calabar Branch About Us:</h2>";
    $about = AboutUs::where('chapter_id', $calabar->id)->first();
    if ($about) {
        echo "<pre>" . json_encode($about->toArray(), JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color:red'>No AboutUs record found for Calabar Branch!</p>";
    }
}

echo "<hr><a href='/about?chapter=Calabar+Branch'>View Public About Page</a>";

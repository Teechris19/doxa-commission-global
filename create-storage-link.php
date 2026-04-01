<?php

// Create storage/app/public directory if it doesn't exist
$publicPath = __DIR__ . '/storage/app/public';
if (!is_dir($publicPath)) {
    mkdir($publicPath, 0755, true);
    echo "Created storage/app/public directory\n";
} else {
    echo "storage/app/public directory already exists\n";
}

// Remove existing symlink/junction if it exists
$linkPath = __DIR__ . '/public/storage';
if (is_link($linkPath) || is_dir($linkPath)) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - use rmdir for junction
        exec("rmdir \"{$linkPath}\"", $output, $return);
        if ($return === 0 || $return === 1) {
            echo "Removed existing storage link\n";
        } else {
            echo "Could not remove existing link, trying del...\n";
            exec("del \"{$linkPath}\"", $output2, $return2);
        }
    } else {
        unlink($linkPath);
        echo "Removed existing storage link\n";
    }
}

// Create symbolic link
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows - create junction
    $target = __DIR__ . '\storage\app\public';
    $link = __DIR__ . '\public\storage';
    exec("mklink /J \"{$link}\" \"{$target}\"", $output, $return);
    if ($return === 0) {
        echo "Created storage link successfully!\n";
    } else {
        echo "Failed to create storage link. Return code: {$return}\n";
        echo "Output: " . implode("\n", $output) . "\n";
    }
} else {
    // Unix/Linux/Mac
    symlink($publicPath, $linkPath);
    echo "Created storage link successfully!\n";
}

echo "\nDone!\n";

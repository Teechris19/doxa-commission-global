<?php

$config = require base_path('vendor/livewire/livewire/config/livewire.php');

// Allow larger temporary uploads for long sermon audio/video files.
$config['temporary_file_upload']['rules'] = ['required', 'file', 'max:512000']; // 500MB
$config['temporary_file_upload']['max_upload_time'] = 60; // minutes

return $config;

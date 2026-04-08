<?php

use App\Models\Sermons;
use App\Models\SermonMedia;
use App\Jobs\ProcessSermonMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::disk('public')->makeDirectory('temp');
    Storage::disk('public')->makeDirectory('sermons/video');
    Storage::disk('public')->makeDirectory('sermons/audio');
    Storage::disk('public')->makeDirectory('sermons/thumbnail');
});

describe('Sermon Media Processing', function () {
    
    test('sermon media job processes video file', function () {
        Queue::fake();
        
        $sermon = Sermons::factory()->create();
        $testFile = 'test_video.mp4';
        
        // Create a test file
        Storage::disk('public')->put('temp/' . $testFile, 'fake video content');
        
        $job = new ProcessSermonMedia(
            sermonId: $sermon->id,
            tempPath: 'temp/' . $testFile,
            type: 'video',
            originalFileName: $testFile
        );
        
        $job->handle();
        
        expect(SermonMedia::where('mediable_id', $sermon->id)->count())->toBe(1);
    });
    
    test('sermon media job processes audio file', function () {
        $sermon = Sermons::factory()->create();
        $testFile = 'test_audio.mp3';
        
        Storage::disk('public')->put('temp/' . $testFile, 'fake audio content');
        
        $job = new ProcessSermonMedia(
            sermonId: $sermon->id,
            tempPath: 'temp/' . $testFile,
            type: 'audio',
            originalFileName: $testFile
        );
        
        $job->handle();
        
        $media = SermonMedia::where('mediable_id', $sermon->id)->first();
        expect($media->type)->toBe('audio');
    });
    
    test('sermon media job creates database record with correct data', function () {
        $sermon = Sermons::factory()->create();
        $testFile = 'test_file.mp4';
        
        Storage::disk('public')->put('temp/' . $testFile, 'fake content');
        
        $job = new ProcessSermonMedia(
            sermonId: $sermon->id,
            tempPath: 'temp/' . $testFile,
            type: 'video',
            originalFileName: $testFile
        );
        
        $job->handle();
        
        $media = SermonMedia::where('mediable_id', $sermon->id)->first();
        
        expect($media->mediable_type)->toBe(Sermons::class);
        expect($media->file_name)->toBe($testFile);
        expect($media->type)->toBe('video');
    });
    
    test('sermon media job handles missing file gracefully', function () {
        $sermon = Sermons::factory()->create();
        
        $job = new ProcessSermonMedia(
            sermonId: $sermon->id,
            tempPath: 'temp/nonexistent.mp4',
            type: 'video',
            originalFileName: 'nonexistent.mp4'
        );
        
        // Should not throw exception
        $job->handle();
        
        expect(SermonMedia::where('mediable_id', $sermon->id)->count())->toBe(0);
    });
    
    test('sermon media job cleans up temp file on failure', function () {
        $sermon = Sermons::factory()->create();
        $testFile = 'test_file.mp4';
        
        Storage::disk('public')->put('temp/' . $testFile, 'fake content');
        
        $job = new ProcessSermonMedia(
            sermonId: 99999, // Invalid ID to cause error
            tempPath: 'temp/' . $testFile,
            type: 'video',
            originalFileName: $testFile
        );
        
        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected to fail
        }
        
        // Temp file should be cleaned up
        expect(Storage::disk('public')->exists('temp/' . $testFile))->toBeFalse();
    });
    
    test('sermon media job moves file to correct location', function () {
        $sermon = Sermons::factory()->create();
        $testFile = 'test_video.mp4';
        
        Storage::disk('public')->put('temp/' . $testFile, 'fake video content');
        
        $job = new ProcessSermonMedia(
            sermonId: $sermon->id,
            tempPath: 'temp/' . $testFile,
            type: 'video',
            originalFileName: $testFile
        );
        
        $job->handle();
        
        // Temp file should be deleted
        expect(Storage::disk('public')->exists('temp/' . $testFile))->toBeFalse();
        
        // File should exist in sermons directory
        $media = SermonMedia::where('mediable_id', $sermon->id)->first();
        expect(Storage::disk('public')->exists($media->file_path))->toBeTrue();
    });
});

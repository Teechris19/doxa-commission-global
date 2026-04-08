<?php

namespace App\Jobs;

use App\Models\Sermons;
use App\Models\SermonMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessSermonMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout for large files

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $sermonId,
        public string $tempPath,
        public string $type,
        public string $originalFileName
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $sermon = Sermons::findOrFail($this->sermonId);

            // Get the temp file
            $tempFullPath = Storage::disk('public')->path($this->tempPath);

            if (!file_exists($tempFullPath)) {
                Log::error("Sermon media temp file not found: {$this->tempPath}");
                return;
            }

            // Get file info
            $fileSize = filesize($tempFullPath);
            $mimeType = mime_content_type($tempFullPath);

            // Generate new path
            $extension = pathinfo($this->originalFileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . time() . '.' . $extension;
            $finalPath = "sermons/{$this->type}/{$newFileName}";

            // Move file from temp to final location
            Storage::disk('public')->move($this->tempPath, $finalPath);

            // Create media record
            SermonMedia::create([
                'mediable_id' => $sermon->id,
                'mediable_type' => Sermons::class,
                'file_name' => $this->originalFileName,
                'file_path' => $finalPath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'type' => $this->type,
            ]);

            Log::info("Sermon {$this->type} processed successfully", [
                'sermon_id' => $this->sermonId,
                'file' => $this->originalFileName,
                'path' => $finalPath
            ]);

        } catch (\Exception $e) {
            Log::error("Error processing sermon media", [
                'sermon_id' => $this->sermonId,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);

            // Clean up temp file if it exists
            if (Storage::disk('public')->exists($this->tempPath)) {
                Storage::disk('public')->delete($this->tempPath);
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Sermon media processing job failed", [
            'sermon_id' => $this->sermonId,
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);

        // Clean up temp file
        if (Storage::disk('public')->exists($this->tempPath)) {
            Storage::disk('public')->delete($this->tempPath);
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sermon_medias', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable'); // polymorphic: sermon_id, sermon_type
            $table->string('file_name');
            $table->string('file_path'); // e.g., 'sermons/audio/uuid.mp3'
            $table->string('mime_type'); // e.g., 'audio/mpeg' or 'video/mp4'
            $table->unsignedBigInteger('file_size'); // In bytes
            $table->string('type'); // 'audio' or 'video'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_media');
    }
};

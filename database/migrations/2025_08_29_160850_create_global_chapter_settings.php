<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();

            // Church-wide identity
            $table->string('church_name')->nullable();
            $table->string('denomination')->nullable();
            $table->string('tagline')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('banner_image')->nullable();

            $table->string('livestream_url')->nullable();
            $table->string('podcast_url')->nullable();
            $table->string('giving_url')->nullable();

            // Social links (HQ-wide)
            $table->json('social_links')->nullable();
            // Example: { "facebook": "...", "youtube": "...", "instagram": "..." }

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Misc / extra configs
            $table->json('extras')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_chapter_settings');
    }
};

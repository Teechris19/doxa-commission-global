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
        Schema::create('chapter_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chapter_id')->unique(); // one settings row per chapter

            // Chapter identity
            $table->string('name')->nullable();
            $table->string('tagline')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner_image')->nullable();

            // Location & contact
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('alt_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('map_location')->nullable();

            // Chapter-specific schedules & socials
            $table->json('service_times')->nullable();   // [{day: "Sunday", time: "9AM"}]
            $table->json('special_events')->nullable();  // [{name: "Easter", date: "2025-04-20"}]
            $table->json('social_links')->nullable();    // {facebook: "...", youtube: "..."}

            // Media & extras
            $table->string('livestream_url')->nullable();
            $table->string('giving_url')->nullable();
            $table->json('extras')->nullable();

            $table->timestamps();

            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

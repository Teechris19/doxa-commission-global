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
        // Create pastors table for "Our Pastor" section
        Schema::create('pastors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('name');
            $table->string('title')->default('Lead Pastor'); // e.g., "Lead Pastor", "Senior Pastor"
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_column')->default(0);
            $table->timestamps();
        });

        // Create service_times table for "Service Times" section
        Schema::create('service_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('category'); // 'sunday' or 'thursday'
            $table->string('service_name'); // e.g., "1st Service", "Bible Study"
            $table->string('time'); // e.g., "7:00 AM - 9:00 AM"
            $table->integer('order_column')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create cta_sections table for "Join Community" section
        Schema::create('cta_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('button_text');
            $table->string('button_link');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add whatsapp_link to conclaves table
        Schema::table('conclaves', function (Blueprint $table) {
            $table->string('whatsapp_link')->nullable()->after('email');
        });

        // Add preview settings to about_us table
        Schema::table('about_us', function (Blueprint $table) {
            $table->integer('conclaves_preview_count')->default(6)->after('is_active'); // Number of conclaves to show on about page
            $table->string('hero_title')->nullable()->after('title'); // Hero section title
            $table->text('hero_subtitle')->nullable()->after('hero_title'); // Hero section subtitle
            $table->string('hero_background_image')->nullable()->after('hero_subtitle'); // Hero background image
            $table->string('title')->nullable()->change(); // Make title nullable
            $table->text('description')->nullable()->change(); // Make description nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conclaves', function (Blueprint $table) {
            $table->dropColumn('whatsapp_link');
        });

        Schema::table('about_us', function (Blueprint $table) {
            $table->dropColumn(['conclaves_preview_count', 'hero_title', 'hero_subtitle', 'hero_background_image']);
        });

        Schema::dropIfExists('cta_sections');
        Schema::dropIfExists('service_times');
        Schema::dropIfExists('pastors');
    }
};

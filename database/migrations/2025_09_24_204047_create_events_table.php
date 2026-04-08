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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Ownership / scoping
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Core details
            $table->string('title');
            $table->string('slug')->nullable(); // unique per chapter for SEO-friendly URLs
            $table->text('description')->nullable();

            // Scheduling
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable()->index();
            $table->string('timezone', 64)->nullable();
            // Venue / media
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('livestream_url')->nullable();
            $table->string('banner')->nullable(); // path/URL to cover image

            // Management
            $table->enum('status', ['draft', 'published', 'cancelled', 'archived'])->default('draft')->index();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('registration_required')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Unique slug per chapter
            $table->unique(['chapter_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

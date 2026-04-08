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
        Schema::create('cell_page_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->nullable()->constrained()->onDelete('set null');
            
            // Hero Section
            $table->string('hero_title')->default('Join a Cell Group');
            $table->string('hero_subtitle')->default('Connect, grow, and fellowship in small groups');
            $table->text('hero_description')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_button_text')->default('Join a Cell');
            
            // Left/Right Text Section
            $table->string('left_heading')->default('HOME CLOSE TO YOU');
            $table->text('right_description')->nullable();
            
            // Center Image
            $table->string('center_image')->nullable();
            
            // Display Settings
            $table->integer('cells_to_display')->default(3);
            
            // FAQs (stored as JSON)
            $table->json('faqs')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cell_page_settings');
    }
};

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
        Schema::create('pastor_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('pastor_name')->nullable();
            $table->string('pastor_title')->nullable()->default('Lead Pastor');
            $table->text('pastor_description')->nullable();
            $table->string('pastor_image')->nullable();
            $table->string('cta_button_text')->nullable()->default('Learn More');
            $table->string('cta_button_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('telegram_url')->nullable();
            $table->string('whatsapp_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastor_settings');
    }
};

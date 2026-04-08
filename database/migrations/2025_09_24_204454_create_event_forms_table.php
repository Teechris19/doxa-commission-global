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
        Schema::create('event_forms', function (Blueprint $table) {
            $table->id();

            // Links
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();

            // Registrant info
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('guests')->default(0);
            $table->json('form');
            // Additional data
            $table->json('answers')->nullable(); // dynamic fields
            $table->text('notes')->nullable();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending')->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'chapter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_forms');
    }
};

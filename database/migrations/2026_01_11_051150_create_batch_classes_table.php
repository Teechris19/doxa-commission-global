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
        Schema::create('batch_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('academy_batches')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('academy_clases')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_classes');
    }
};

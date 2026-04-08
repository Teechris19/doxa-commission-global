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
        Schema::create('partnership_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('label');
            $table->string('name');
            $table->enum('type', ['text', 'textarea', 'select', 'number', 'email', 'tel', 'date', 'checkbox']);
            $table->text('options')->nullable()->comment('JSON for select options');
            $table->text('description')->nullable();
            $table->text('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnership_form_fields');
    }
};

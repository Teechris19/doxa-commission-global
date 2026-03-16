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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');

            $table->string('name'); // e.g. "Sunday Worship", "Bible Study", "Youth Service"
            $table->string('day_of_week')->nullable(); // e.g. Sunday, Wednesday
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->boolean('is_recurring')->default(true); // recurring vs special one-off
            $table->date('special_date')->nullable(); // for non-recurring events

            $table->string('location')->nullable(); // could be chapter hall, alternate venue
            $table->string('livestream_url')->nullable();
            $table->string('message_url')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service');
    }
};

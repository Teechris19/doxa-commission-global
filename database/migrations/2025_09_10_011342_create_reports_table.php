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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('event_type', 100)->nullable()->index();
            $table->string('report_path')->nullable();
            $table->longText('report')->nullable();
            $table->enum('level', ['team', 'chapter', 'hq'])->default('team')->index();
            $table->unsignedBigInteger('chapter_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('set null');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

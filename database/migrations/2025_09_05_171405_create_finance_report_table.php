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
        Schema::create('finance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_id')->constrained('finances')->onDelete('cascade');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->date('date');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('draft'); // e.g., 'draft', 'submitted', 'approved'
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['date', 'team_id', 'chapter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_report');
    }
};

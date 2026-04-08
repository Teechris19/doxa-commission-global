<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class  extends Migration{
    public function up()
    {
        Schema::create('attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendance')->onDelete('cascade');
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

    public function down()
    {
        Schema::dropIfExists('attendance_reports');
    }
};
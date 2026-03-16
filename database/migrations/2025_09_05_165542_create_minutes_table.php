<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMinutesTable extends Migration
{
    public function up()
    {
        Schema::create('minutes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->date('meeting_date');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('draft'); // e.g., 'draft', 'approved'
            $table->text('attendees')->nullable(); // e.g., JSON or comma-separated
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['meeting_date', 'team_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('minutes');
    }
}
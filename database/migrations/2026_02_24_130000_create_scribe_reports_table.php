<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scribe_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // weekly_service, sunday_summary, attendance_summary, program_documentation
            $table->string('title');
            $table->date('service_date')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('pending'); // pending, approved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scribe_reports');
    }
};

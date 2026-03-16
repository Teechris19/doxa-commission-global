<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_outreach_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->nullOnDelete();
            $table->foreignId('mission_report_id')->nullable()->constrained('mission_reports')->nullOnDelete();
            $table->string('location');
            $table->longText('team_members')->nullable();
            $table->longText('materials_used')->nullable();
            $table->longText('results')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_outreach_details');
    }
};

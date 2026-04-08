<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table already exists
        $tableExists = DB::select("SHOW TABLES LIKE 'attendance_sessions'");
        
        if (empty($tableExists)) {
            Schema::create('attendance_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->string('session_type');
                $table->string('session_name');
                $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('cascade');
                $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
                $table->string('location')->nullable();
                $table->date('date');
                $table->string('status')->default('open');
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index(['chapter_id', 'date']);
                $table->index(['status', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};

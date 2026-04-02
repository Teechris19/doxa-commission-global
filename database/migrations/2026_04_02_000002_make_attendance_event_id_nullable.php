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
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Make attendance_event_id nullable if it exists
            if (Schema::hasColumn('attendance_sessions', 'attendance_event_id')) {
                $table->foreignId('attendance_event_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'attendance_event_id')) {
                $table->foreignId('attendance_event_id')->nullable(false)->change();
            }
        });
    }
};

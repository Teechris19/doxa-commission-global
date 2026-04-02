<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendance_sessions')) {
            Schema::create('attendance_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->string('session_type')->default('custom');
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
            return;
        }

        // Table exists - add missing columns one by one
        $connection = Schema::getConnection();
        $grammar = $connection->getSchemaGrammar();
        
        // Check and add chapter_id
        if (!Schema::hasColumn('attendance_sessions', 'chapter_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->foreignId('chapter_id')->after('id')->nullable()->constrained('chapters')->onDelete('cascade');
            });
        }
        
        // Check and add created_by
        if (!Schema::hasColumn('attendance_sessions', 'created_by')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('chapter_id')->constrained('users')->onDelete('cascade');
            });
        }
        
        // Check and add session_type
        if (!Schema::hasColumn('attendance_sessions', 'session_type')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->string('session_type')->default('custom')->after('created_by');
            });
        }
        
        // Check and add session_name
        if (!Schema::hasColumn('attendance_sessions', 'session_name')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->string('session_name')->nullable()->after('session_type');
            });
        }
        
        // Check and add service_id
        if (!Schema::hasColumn('attendance_sessions', 'service_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->foreignId('service_id')->nullable()->after('session_name')->constrained('services')->onDelete('cascade');
            });
        }
        
        // Check and add event_id
        if (!Schema::hasColumn('attendance_sessions', 'event_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->after('service_id')->constrained('events')->onDelete('cascade');
            });
        }
        
        // Check and add location
        if (!Schema::hasColumn('attendance_sessions', 'location')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->string('location')->nullable()->after('event_id');
            });
        }
        
        // Check and add status
        if (!Schema::hasColumn('attendance_sessions', 'status')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->string('status')->default('open')->after('date');
            });
        }
        
        // Check and add closed_at
        if (!Schema::hasColumn('attendance_sessions', 'closed_at')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['event_id']);
            
            $table->dropColumn(['chapter_id', 'created_by', 'session_type', 'session_name', 'service_id', 'event_id', 'location', 'status', 'closed_at']);
        });
    }
};

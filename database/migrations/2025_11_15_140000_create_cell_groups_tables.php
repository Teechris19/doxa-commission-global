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
        // Cell Groups Table - Mini churches within a chapter
        Schema::create('cell_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('meeting_day')->nullable(); // e.g., "Tuesday"
            $table->time('meeting_time')->nullable();
            $table->string('location')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('max_members')->default(15);
            $table->string('phone')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cell Leaders Table - Leaders of cell groups
        Schema::create('cell_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cell_group_id')->constrained('cell_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_primary')->default(true); // Primary or assistant leader
            $table->timestamps();
        });

        // Cell Members Table - Members of cell groups
        Schema::create('cell_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cell_group_id')->constrained('cell_groups')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->string('name'); // For non-registered members
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('joined_at');
            $table->enum('status', ['active', 'inactive', 'transferred'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Cell Attendance Table - Track cell meeting attendance
        Schema::create('cell_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cell_group_id')->constrained('cell_groups')->cascadeOnDelete();
            $table->foreignId('cell_member_id')->constrained('cell_members')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->boolean('present')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['cell_member_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cell_attendance');
        Schema::dropIfExists('cell_members');
        Schema::dropIfExists('cell_leaders');
        Schema::dropIfExists('cell_groups');
    }
};

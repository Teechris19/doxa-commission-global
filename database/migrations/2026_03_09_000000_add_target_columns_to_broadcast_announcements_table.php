<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            $table->enum('target_type', ['admin_dashboard', 'user_toast', 'both'])->default('both')->after('channel');
            $table->enum('creator_type', ['super_admin', 'admin'])->nullable()->after('target_type');
            $table->foreignId('created_by')->nullable()->after('creator_type')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['target_type', 'creator_type', 'created_by']);
        });
    }
};

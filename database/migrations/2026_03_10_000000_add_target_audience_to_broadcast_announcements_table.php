<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            $table->enum('target_audience', ['all_users', 'admins', 'team_leads'])->default('all_users')->after('target_type');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });
    }
};

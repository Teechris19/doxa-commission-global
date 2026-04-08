<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_forms', function (Blueprint $table) {
            $table->timestamp('reminder_24h_sent_at')->nullable()->after('status');
            $table->timestamp('reminder_2h_sent_at')->nullable()->after('reminder_24h_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_forms', function (Blueprint $table) {
            $table->dropColumn(['reminder_24h_sent_at', 'reminder_2h_sent_at']);
        });
    }
};

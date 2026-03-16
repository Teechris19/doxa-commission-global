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
        Schema::table('account_events', function (Blueprint $table) {
            $table->dateTime('registered_at')->nullable()->after('event_id');
            $table->enum('status', ['registered', 'attended', 'cancelled', 'pending'])->default('registered')->after('registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_events', function (Blueprint $table) {
            $table->dropColumn(['registered_at', 'status']);
        });
    }
};

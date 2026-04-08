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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('requires_partners')->default(false)->after('registration_required');
            $table->timestamp('partnership_deadline')->nullable()->after('requires_partners');
            $table->text('partnership_description')->nullable()->after('partnership_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['requires_partners', 'partnership_deadline', 'partnership_description']);
        });
    }
};

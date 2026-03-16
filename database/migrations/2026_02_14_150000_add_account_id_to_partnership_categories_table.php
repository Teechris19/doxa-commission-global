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
        if (!Schema::hasColumn('partnership_categories', 'account_id')) {
            Schema::table('partnership_categories', function (Blueprint $table) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('chapter_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('partnership_categories', 'account_id')) {
            Schema::table('partnership_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }
    }
};

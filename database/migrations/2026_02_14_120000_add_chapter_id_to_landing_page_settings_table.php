<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_page_settings', 'chapter_id')) {
                $table->foreignId('chapter_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('chapters')
                    ->nullOnDelete();

                $table->index('chapter_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            if (Schema::hasColumn('landing_page_settings', 'chapter_id')) {
                $table->dropConstrainedForeignId('chapter_id');
            }
        });
    }
};

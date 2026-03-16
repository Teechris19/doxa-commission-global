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
        Schema::table('landing_page_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_page_settings', 'services')) {
                $table->json('services')->nullable();
            }
            if (!Schema::hasColumn('landing_page_settings', 'number_of_testimonies')) {
                $table->integer('number_of_testimonies')->default(5);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            $table->dropColumn(['services', 'number_of_testimonies']);
        });
    }
};

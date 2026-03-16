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
        Schema::table('believers_academies', function (Blueprint $table) {
            $table->string('certificate_template')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('believers_academies', function (Blueprint $table) {
            $table->dropColumn('certificate_template');
        });
    }
};

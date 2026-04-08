<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->text('footer_description')->nullable()->after('social_links');
            $table->string('footer_address')->nullable()->after('footer_description');
            $table->string('footer_phone')->nullable()->after('footer_address');
            $table->string('footer_email')->nullable()->after('footer_phone');
            $table->json('footer_services')->nullable()->after('footer_email');
        });
    }

    public function down(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_description',
                'footer_address',
                'footer_phone',
                'footer_email',
                'footer_services',
            ]);
        });
    }
};

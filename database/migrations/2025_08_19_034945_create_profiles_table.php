<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('chapter_id')->nullable()->index('chapter_id_index_profile');
            // Basic personal info
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->date('dob')->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();

            // Church-specific
            $table->string('marital_status')->nullable(); // single, married, widowed
            $table->date('wedding_anniversary')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->string('education_level')->nullable();
            $table->string('baptism_status')->nullable(); // e.g. baptized, not baptized
            $table->date('membership_date')->nullable(); // when they joined

            // Media
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};

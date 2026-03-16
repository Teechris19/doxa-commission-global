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
        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('preferred_location')->nullable(); // North America, Europe, Asia-Pacific, etc.
            $table->text('partnership_interests')->nullable(); // Description of partnership interests
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'active'])->default('pending');
            $table->text('notes')->nullable(); // Internal notes from admin
            $table->string('organization')->nullable(); // Company/Organization name
            $table->string('website')->nullable();
            $table->enum('partnership_type', ['financial', 'strategic', 'ministry', 'technology', 'other'])->nullable();
            $table->decimal('proposed_amount', 10, 2)->nullable(); // If financial partnership
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Admin assigned to handle
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['status', 'created_at']);
            $table->index(['preferred_location']);
            $table->index(['email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnerships');
    }
};

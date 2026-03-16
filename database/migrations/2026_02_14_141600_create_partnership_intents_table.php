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
        Schema::create('partnership_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('partnership_category_id')->nullable()->constrained('partnership_categories')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();

            $table->enum('intent_type', ['general', 'event', 'project'])->default('general');
            $table->string('title');
            $table->decimal('pledge_amount', 12, 2)->nullable();
            $table->string('pledge_currency', 3)->default('NGN');
            $table->enum('pledge_frequency', ['one_time', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])->default('one_time');
            $table->enum('status', ['draft', 'pending', 'reviewing', 'approved', 'declined', 'withdrawn'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('pledged_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['chapter_id', 'status']);
            $table->index(['chapter_id', 'intent_type']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnership_intents');
    }
};


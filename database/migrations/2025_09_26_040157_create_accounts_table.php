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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name'); // e.g., "InnovateTech Holdings"
            $table->string('account_number'); // e.g., "987-654-321-0"
            $table->string('bank_name'); // e.g., "Global Union Bank"
            $table->string('bank_code')->nullable(); // Bank routing/sort code
            $table->string('swift_code')->nullable(); // For international transfers
            $table->string('iban')->nullable(); // International Bank Account Number
            $table->enum('account_type', ['checking', 'savings', 'business', 'ministry', 'donation'])->default('ministry');
            $table->string('currency', 3)->default('USD'); // ISO currency code
            $table->string('region'); // North America, Europe, Asia-Pacific, etc.
            $table->string('country')->nullable();
            $table->text('description')->nullable(); // Purpose or description of the account
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_online_payments')->default(false);
            $table->boolean('accepts_international')->default(false);
            $table->json('supported_payment_methods')->nullable(); // ['bank_transfer', 'credit_card', 'paypal', etc.]
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Minimum transaction amount
            $table->decimal('maximum_amount', 10, 2)->nullable(); // Maximum transaction amount
            $table->text('special_instructions')->nullable(); // Special payment instructions
            $table->string('contact_person')->nullable(); // Person responsible for this account
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['region', 'currency']);
            $table->index(['is_active']);
            $table->index(['account_type']);
            $table->unique(['account_number', 'bank_code']); // Prevent duplicate accounts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

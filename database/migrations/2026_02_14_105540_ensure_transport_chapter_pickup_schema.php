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
        if (!Schema::hasTable('pickup_locations')) {
            Schema::create('pickup_locations', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('address')->nullable();
                $table->text('description')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('contact_phone')->nullable();
                $table->time('pickup_time')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedBigInteger('chapter_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('pickup_locations', function (Blueprint $table): void {
                if (!Schema::hasColumn('pickup_locations', 'address')) {
                    $table->string('address')->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'contact_person')) {
                    $table->string('contact_person')->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'contact_phone')) {
                    $table->string('contact_phone')->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'pickup_time')) {
                    $table->time('pickup_time')->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'chapter_id')) {
                    $table->unsignedBigInteger('chapter_id')->nullable();
                }
                if (!Schema::hasColumn('pickup_locations', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (!Schema::hasTable('transports')) {
            Schema::create('transports', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('phone');
                $table->text('pickup_location');
                $table->unsignedBigInteger('chapter_id')->nullable();
                $table->unsignedBigInteger('pickup_location_id')->nullable();
                $table->time('pickup_time')->nullable();
                $table->string('user_address')->nullable();
                $table->decimal('user_latitude', 10, 7)->nullable();
                $table->decimal('user_longitude', 10, 7)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('transports', function (Blueprint $table): void {
                if (!Schema::hasColumn('transports', 'chapter_id')) {
                    $table->unsignedBigInteger('chapter_id')->nullable();
                }
                if (!Schema::hasColumn('transports', 'pickup_location_id')) {
                    $table->unsignedBigInteger('pickup_location_id')->nullable();
                }
                if (!Schema::hasColumn('transports', 'pickup_time')) {
                    $table->time('pickup_time')->nullable();
                }
                if (!Schema::hasColumn('transports', 'user_address')) {
                    $table->string('user_address')->nullable();
                }
                if (!Schema::hasColumn('transports', 'user_latitude')) {
                    $table->decimal('user_latitude', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('transports', 'user_longitude')) {
                    $table->decimal('user_longitude', 10, 7)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transports')) {
            Schema::table('transports', function (Blueprint $table): void {
                if (Schema::hasColumn('transports', 'pickup_location_id')) {
                    $table->dropColumn('pickup_location_id');
                }
                if (Schema::hasColumn('transports', 'chapter_id')) {
                    $table->dropColumn('chapter_id');
                }
                if (Schema::hasColumn('transports', 'pickup_time')) {
                    $table->dropColumn('pickup_time');
                }
                if (Schema::hasColumn('transports', 'user_address')) {
                    $table->dropColumn('user_address');
                }
                if (Schema::hasColumn('transports', 'user_latitude')) {
                    $table->dropColumn('user_latitude');
                }
                if (Schema::hasColumn('transports', 'user_longitude')) {
                    $table->dropColumn('user_longitude');
                }
            });
        }

        if (Schema::hasTable('pickup_locations')) {
            Schema::table('pickup_locations', function (Blueprint $table): void {
                if (Schema::hasColumn('pickup_locations', 'address')) {
                    $table->dropColumn('address');
                }
                if (Schema::hasColumn('pickup_locations', 'latitude')) {
                    $table->dropColumn('latitude');
                }
                if (Schema::hasColumn('pickup_locations', 'longitude')) {
                    $table->dropColumn('longitude');
                }
                if (Schema::hasColumn('pickup_locations', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }
    }
};

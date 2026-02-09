<?php

declare(strict_types=1);

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
        Schema::table('users', function (Blueprint $table) {
            // Add branch_id for future multi-branch support
            $table->unsignedBigInteger('branch_id')->nullable()->after('id')->index();

            // Add phone field
            $table->string('phone', 20)->nullable()->unique()->after('name');

            // Make email nullable
            $table->string('email')->nullable()->change();

            // Make password nullable (for OTP-only users)
            $table->string('password')->nullable()->change();

            // Add profile fields
            $table->enum('gender', ['male', 'female'])->nullable()->after('role');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('country', 100)->nullable()->after('date_of_birth');

            // Add phone verification timestamp
            $table->timestamp('phone_verified_at')->nullable()->after('country');

            // Add active status
            $table->boolean('is_active')->default(true)->after('phone_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropColumn([
                'branch_id',
                'phone',
                'gender',
                'date_of_birth',
                'country',
                'phone_verified_at',
                'is_active',
            ]);

            // Revert email and password to required
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};

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
        Schema::table('branches', function (Blueprint $table): void {
            // Convert name from string to JSON for translations
            $table->json('name')->change();

            // Convert address from text to JSON for translations
            $table->json('address')->nullable()->change();

            // Add description as JSON for translations
            $table->json('description')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            // Revert name back to string
            $table->string('name')->change();

            // Revert address back to text
            $table->text('address')->nullable()->change();

            // Remove description column
            $table->dropColumn('description');
        });
    }
};

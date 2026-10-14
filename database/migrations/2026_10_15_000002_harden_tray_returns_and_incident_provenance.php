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
        Schema::table('tray_returns', function (Blueprint $table) {
            $table->unsignedInteger('expected_trays')->nullable()->change();
            $table->boolean('is_expected_verified')->default(false)->after('expected_trays');
            $table->string('manifest_reference', 100)->nullable()->after('is_expected_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tray_returns', function (Blueprint $table) {
            $table->dropColumn(['is_expected_verified', 'manifest_reference']);
            $table->unsignedInteger('expected_trays')->nullable(false)->change();
        });
    }
};

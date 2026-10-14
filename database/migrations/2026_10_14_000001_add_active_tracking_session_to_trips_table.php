<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-09 Server-authoritative tracking session identity hardening.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('active_tracking_session_id', 64)->nullable()->after('tracking_status')->index();
        });

        Schema::table('gps_locations', function (Blueprint $table) {
            $table->string('session_id', 64)->nullable()->after('driver_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_locations', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['active_tracking_session_id']);
            $table->dropColumn('active_tracking_session_id');
        });
    }
};

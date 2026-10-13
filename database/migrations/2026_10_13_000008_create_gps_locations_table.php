<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-09 GPS Telemetry and Real Location Tracking.
     */
    public function up(): void
    {
        Schema::create('gps_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->decimal('speed_mps', 8, 2)->nullable();
            $table->decimal('heading_deg', 6, 2)->nullable();
            $table->dateTime('recorded_at');
            $table->dateTime('received_at');
            $table->timestamps();

            $table->index(['trip_id', 'recorded_at']);
            $table->index(['driver_id', 'recorded_at']);
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->decimal('last_latitude', 10, 7)->nullable()->after('last_location_at');
            $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            $table->decimal('last_accuracy_m', 8, 2)->nullable()->after('last_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['last_latitude', 'last_longitude', 'last_accuracy_m']);
        });

        Schema::dropIfExists('gps_locations');
    }
};

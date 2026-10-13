<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-08 Dispatch Gate Hardening, Physical Loading Verification & Smart Maps.
     */
    public function up(): void
    {
        // 1. Add verification and routing columns to trips
        Schema::table('trips', function (Blueprint $table) {
            $table->boolean('loading_verified')->default(false)->after('vehicle_check_notes');
            $table->dateTime('loading_verified_at')->nullable()->after('loading_verified');
            $table->foreignId('loading_verified_by')->nullable()->after('loading_verified_at')->constrained('users')->nullOnDelete();
            $table->text('loading_notes')->nullable()->after('loading_verified_by');

            $table->boolean('dispatch_approved')->default(false)->after('loading_notes');
            $table->dateTime('dispatch_approved_at')->nullable()->after('dispatch_approved');
            $table->foreignId('dispatch_approved_by')->nullable()->after('dispatch_approved_at')->constrained('users')->nullOnDelete();
            $table->text('dispatch_notes')->nullable()->after('dispatch_approved_by');

            $table->longText('route_geometry')->nullable()->after('dispatch_notes');
            $table->unsignedInteger('total_distance_m')->nullable()->after('route_geometry');
            $table->unsignedInteger('estimated_duration_s')->nullable()->after('total_distance_m');
        });

        // 2. Add explicit planned and loaded portions to trip_stops for denormalized fast query
        Schema::table('trip_stops', function (Blueprint $table) {
            $table->unsignedInteger('planned_portions')->default(0)->after('sequence_number');
            $table->unsignedInteger('loaded_portions')->nullable()->after('planned_portions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_stops', function (Blueprint $table) {
            $table->dropColumn(['planned_portions', 'loaded_portions']);
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['loading_verified_by']);
            $table->dropForeign(['dispatch_approved_by']);
            $table->dropColumn([
                'loading_verified',
                'loading_verified_at',
                'loading_verified_by',
                'loading_notes',
                'dispatch_approved',
                'dispatch_approved_at',
                'dispatch_approved_by',
                'dispatch_notes',
                'route_geometry',
                'total_distance_m',
                'estimated_duration_s',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-07 Integrity Hardening & PBI-08 Logistics System Tables.
     */
    public function up(): void
    {
        // 1. Add exception workflow columns to food_batches
        Schema::table('food_batches', function (Blueprint $table) {
            $table->foreignId('evidence_exception_requested_by')->nullable()->after('evidence_exception_reason')->constrained('users')->nullOnDelete();
            $table->string('evidence_exception_status', 25)->default('NONE')->after('evidence_exception_requested_by');
        });

        // 2. Add released_portions to delivery_items for strict reconciliation
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->unsignedInteger('released_portions')->nullable()->after('planned_portions');
        });

        // 3. Create vehicles table
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->restrictOnDelete();
            $table->string('plate_number', 24)->unique();
            $table->string('vehicle_name', 80)->nullable();
            $table->string('vehicle_type', 40); // e.g. MOTORCYCLE, VAN, TRUCK, PICKUP
            $table->unsignedInteger('capacity_portions')->nullable();
            $table->string('capacity_note', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sppg_id', 'is_active']);
        });

        // 4. Create trips table
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('trip_code', 60)->unique();
            $table->string('trip_type', 20)->default('DELIVERY'); // DELIVERY, PICKUP, MIXED
            $table->date('service_date');
            $table->dateTime('planned_departure_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->boolean('vehicle_check_passed')->nullable();
            $table->dateTime('vehicle_checked_at')->nullable();
            $table->text('vehicle_check_notes')->nullable();
            $table->string('tracking_status', 25)->default('OFF');
            $table->dateTime('tracking_started_at')->nullable();
            $table->dateTime('tracking_ended_at')->nullable();
            $table->dateTime('last_location_at')->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['sppg_id', 'service_date']);
            $table->index(['driver_id', 'service_date']);
            $table->index(['vehicle_id', 'service_date']);
            $table->index(['status']);
        });

        // 5. Create trip_stops table
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->string('stop_type', 20)->default('DELIVERY'); // DELIVERY, PICKUP
            $table->unsignedSmallInteger('sequence_number');
            $table->dateTime('planned_arrival_at')->nullable();
            $table->dateTime('estimated_arrival_at')->nullable();
            $table->dateTime('eta_updated_at')->nullable();
            $table->unsignedInteger('remaining_distance_m')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('handed_over_at')->nullable();
            $table->dateTime('pre_arrival_notified_at')->nullable();
            $table->string('status', 35)->default('PENDING');
            $table->timestamps();

            $table->index(['trip_id', 'sequence_number']);
            $table->index(['school_id']);
            $table->index(['status']);
        });

        // 6. Add foreign key from delivery_items.trip_stop_id to trip_stops.id
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->foreign('trip_stop_id')->references('id')->on('trip_stops')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->dropForeign(['trip_stop_id']);
            $table->dropColumn('released_portions');
        });

        Schema::dropIfExists('trip_stops');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('vehicles');

        Schema::table('food_batches', function (Blueprint $table) {
            $table->dropForeign(['evidence_exception_requested_by']);
            $table->dropColumn([
                'evidence_exception_requested_by',
                'evidence_exception_status',
            ]);
        });
    }
};

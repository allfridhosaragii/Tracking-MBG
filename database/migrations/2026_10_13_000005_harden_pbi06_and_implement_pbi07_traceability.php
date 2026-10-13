<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for PBI-06 Hardening and PBI-07 Batch Traceability.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. CRITICAL GATE E: Fix master data foreign keys in MySQL to RESTRICT on delete
        if ($driver === 'mysql') {
            // production_plans
            Schema::table('production_plans', function (Blueprint $table) {
                // Drop existing cascading foreign keys safely
                $table->dropForeign(['sppg_id']);
                $table->dropForeign(['menu_id']);
                $table->dropForeign(['created_by']);

                // Re-add with restrictOnDelete
                $table->foreign('sppg_id')->references('id')->on('sppgs')->restrictOnDelete();
                $table->foreign('menu_id')->references('id')->on('menus')->restrictOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            });

            // production_allocations
            Schema::table('production_allocations', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropForeign(['portion_request_id']);

                $table->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
                $table->foreign('portion_request_id')->references('id')->on('daily_portion_requests')->restrictOnDelete();
            });
        }

        // 2. Add hardening columns to food_batches
        Schema::table('food_batches', function (Blueprint $table) {
            $table->unsignedInteger('accepted_portions')->nullable()->after('actual_portions');
            $table->boolean('has_evidence_exception')->default(false)->after('notes');
            $table->text('evidence_exception_reason')->nullable()->after('has_evidence_exception');
            $table->foreignId('evidence_exception_approved_by')->nullable()->after('evidence_exception_reason')->constrained('users')->nullOnDelete();
        });

        // 3. Add hardening columns to quality_checks
        Schema::table('quality_checks', function (Blueprint $table) {
            $table->unsignedInteger('accepted_portions')->nullable()->after('temperature_c');
            $table->unsignedInteger('rejected_portions')->default(0)->after('accepted_portions');
            $table->string('sop_reference', 100)->nullable()->default('SOP-BGN-MUTU-01')->after('rejected_portions');
            $table->text('decision_reason')->nullable()->after('sop_reference');
            $table->boolean('taste_tested')->default(true)->after('taste_ok');
            $table->text('taste_skipped_reason')->nullable()->after('taste_tested');
        });

        // 4. Create delivery_items table for PBI-07 Traceability (links food_batches to production_allocations and school destinations)
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_batch_id')->constrained('food_batches')->restrictOnDelete();
            $table->foreignId('production_allocation_id')->constrained('production_allocations')->restrictOnDelete();
            $table->unsignedBigInteger('trip_stop_id')->nullable();
            $table->unsignedInteger('planned_portions');
            $table->unsignedInteger('loaded_portions')->nullable();
            $table->unsignedInteger('delivered_portions')->nullable();
            $table->string('status', 35)->default('PENDING_DISPATCH');
            $table->timestamps();

            $table->index(['food_batch_id', 'production_allocation_id']);
            $table->index(['trip_stop_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_items');

        Schema::table('quality_checks', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_portions',
                'rejected_portions',
                'sop_reference',
                'decision_reason',
                'taste_tested',
                'taste_skipped_reason',
            ]);
        });

        Schema::table('food_batches', function (Blueprint $table) {
            $table->dropForeign(['evidence_exception_approved_by']);
            $table->dropColumn([
                'accepted_portions',
                'has_evidence_exception',
                'evidence_exception_reason',
                'evidence_exception_approved_by',
            ]);
        });
    }
};

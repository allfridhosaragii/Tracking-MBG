<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-11: Trip Closure, Fleet Return & Operational Reconciliation.
     */
    public function up(): void
    {
        // 1. Add return and closure tracking columns to trips table
        Schema::table('trips', function (Blueprint $table) {
            $table->dateTime('return_started_at')->nullable()->after('completed_at');
            $table->dateTime('arrived_at_sppg_at')->nullable()->after('return_started_at');
            $table->text('return_notes')->nullable()->after('arrived_at_sppg_at');
            $table->foreignId('closed_by')->nullable()->after('return_notes')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_trays_returned')->nullable()->after('closed_by');
            $table->unsignedInteger('total_trays_damaged')->nullable()->after('total_trays_returned');
            $table->unsignedInteger('total_trays_missing')->nullable()->after('total_trays_damaged');
            $table->text('tray_reconciliation_notes')->nullable()->after('total_trays_missing');
        });

        // 2. Create tray_returns table matching DATA-DICTIONARY.md
        Schema::create('tray_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_service_record_id')->unique()->constrained('school_service_records')->cascadeOnDelete();
            $table->foreignId('pickup_trip_stop_id')->nullable()->constrained('trip_stops')->nullOnDelete();
            $table->unsignedInteger('expected_trays');
            $table->unsignedInteger('ready_count')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->unsignedInteger('picked_up_count')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->unsignedInteger('returned_count')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->unsignedInteger('damaged_count')->nullable();
            $table->unsignedInteger('missing_count')->nullable();
            $table->string('status', 35)->default('IN_USE');
            $table->text('discrepancy_reason')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        // 3. Create incidents table matching DATA-DICTIONARY.md
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('category', 40);
            $table->string('severity', 20)->default('MEDIUM');
            $table->string('status', 25)->default('OPEN');
            $table->text('description');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['sppg_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['reported_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('tray_returns');

        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'return_started_at',
                'arrived_at_sppg_at',
                'return_notes',
                'closed_by',
                'total_trays_returned',
                'total_trays_damaged',
                'total_trays_missing',
                'tray_reconciliation_notes',
            ]);
        });
    }
};

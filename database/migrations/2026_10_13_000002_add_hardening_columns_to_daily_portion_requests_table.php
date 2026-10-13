<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-04 Hardening: Add revision isolation, escalation and exception approval columns.
     */
    public function up(): void
    {
        Schema::table('daily_portion_requests', function (Blueprint $table) {
            $table->unsignedInteger('revised_portions')->nullable()->after('requested_portions');
            $table->string('previous_status', 25)->nullable()->after('status');
            $table->dateTime('revision_requested_at')->nullable()->after('locked_at');
            $table->foreignId('revision_reviewed_by')->nullable()->after('revision_requested_at')->constrained('users')->nullOnDelete();
            $table->dateTime('revision_reviewed_at')->nullable()->after('revision_reviewed_by');
            $table->text('revision_rejection_reason')->nullable()->after('revision_reviewed_at');
            $table->boolean('is_exceeds_approved')->default(false)->after('revision_rejection_reason');
            $table->foreignId('exceeds_approved_by')->nullable()->after('is_exceeds_approved')->constrained('users')->nullOnDelete();
            $table->dateTime('exceeds_approved_at')->nullable()->after('exceeds_approved_by');
            $table->text('exceeds_rejection_reason')->nullable()->after('exceeds_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_portion_requests', function (Blueprint $table) {
            $table->dropForeign(['revision_reviewed_by']);
            $table->dropForeign(['exceeds_approved_by']);
            $table->dropColumn([
                'revised_portions',
                'previous_status',
                'revision_requested_at',
                'revision_reviewed_by',
                'revision_reviewed_at',
                'revision_rejection_reason',
                'is_exceeds_approved',
                'exceeds_approved_by',
                'exceeds_approved_at',
                'exceeds_rejection_reason',
            ]);
        });
    }
};

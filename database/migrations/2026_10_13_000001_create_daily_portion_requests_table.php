<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-04: Daily Portion Requests table conforming to DATA-DICTIONARY.md
     */
    public function up(): void
    {
        Schema::create('daily_portion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('registered_count');
            $table->unsignedInteger('present_count')->nullable();
            $table->unsignedInteger('requested_portions')->nullable();
            $table->string('status', 25)->default('DRAFT'); // DRAFT, CONFIRMED, LOCKED, REVISED, CANCELLED
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->text('revision_reason')->nullable();
            $table->timestamps();

            // Unique constraint per school and service date
            $table->unique(['school_id', 'service_date'], 'daily_portion_requests_school_date_unique');
            $table->index(['service_date', 'status'], 'daily_portion_requests_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_portion_requests');
    }
};

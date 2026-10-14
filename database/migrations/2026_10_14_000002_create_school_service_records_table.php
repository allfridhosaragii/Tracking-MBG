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
        Schema::create('school_service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_stop_id')->unique()->constrained('trip_stops')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->nullable();
            $table->unsignedInteger('expected_portions');
            $table->unsignedInteger('received_portions')->nullable();
            $table->unsignedInteger('damaged_portions')->default(0);
            $table->unsignedInteger('shortage_portions')->default(0);
            $table->dateTime('distribution_started_at')->nullable();
            $table->unsignedInteger('served_portions')->nullable();
            $table->unsignedInteger('leftover_portions')->nullable();
            $table->dateTime('meal_completed_at')->nullable();
            $table->string('status', 35)->default('WAITING')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_service_records');
    }
};

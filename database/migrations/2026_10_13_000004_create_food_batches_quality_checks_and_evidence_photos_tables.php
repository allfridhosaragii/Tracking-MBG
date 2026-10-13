<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for PBI-06 Production Execution, Food Batches, Stage Logs, QC, and Photos.
     */
    public function up(): void
    {
        Schema::create('food_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->restrictOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->restrictOnDelete();
            $table->string('batch_code', 60)->unique();
            $table->string('portion_category', 35)->nullable();
            $table->unsignedInteger('planned_portions');
            $table->unsignedInteger('actual_portions')->nullable();
            $table->string('status', 30)->default('PREPARING');
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['production_plan_id', 'status']);
        });

        Schema::create('production_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_batch_id')->constrained('food_batches')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['food_batch_id', 'stage']);
        });

        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_batch_id')->constrained('food_batches')->cascadeOnDelete();
            $table->unsignedBigInteger('trip_stop_id')->nullable();
            $table->string('check_stage', 45)->default('BEFORE_DISPATCH');
            $table->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $table->boolean('appearance_ok')->nullable();
            $table->boolean('aroma_ok')->nullable();
            $table->boolean('texture_ok')->nullable();
            $table->boolean('taste_ok')->nullable();
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->string('conclusion', 20); // SAFE, UNSAFE, HOLD
            $table->text('notes')->nullable();
            $table->dateTime('checked_at');
            $table->timestamps();

            $table->index(['food_batch_id', 'check_stage']);
        });

        Schema::create('evidence_photos', function (Blueprint $table) {
            $table->id();
            $table->string('evidenceable_type', 100);
            $table->unsignedBigInteger('evidenceable_id');
            $table->string('evidence_type', 50);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('file_path', 500);
            $table->string('mime_type', 70);
            $table->unsignedInteger('file_size');
            $table->dateTime('captured_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['evidenceable_type', 'evidenceable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_photos');
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('production_stage_logs');
        Schema::dropIfExists('food_batches');
    }
};

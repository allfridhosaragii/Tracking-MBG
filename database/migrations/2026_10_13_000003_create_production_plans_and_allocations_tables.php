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
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->restrictOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->restrictOnDelete();
            $table->date('service_date');
            $table->dateTime('planned_start_at')->nullable();
            $table->string('status', 35)->default('PLANNED');
            $table->text('notes')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['sppg_id', 'service_date']);
            $table->unique(['sppg_id', 'service_date', 'menu_id'], 'prod_plans_sppg_date_menu_unique');
        });

        Schema::create('production_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->cascadeOnDelete();
            $table->foreignId('portion_request_id')->constrained('daily_portion_requests')->restrictOnDelete();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->unsignedInteger('allocated_portions');
            $table->timestamps();

            $table->unique(['production_plan_id', 'school_id'], 'prod_alloc_plan_school_unique');
            $table->unique(['production_plan_id', 'portion_request_id'], 'prod_alloc_plan_request_unique');
            $table->index(['school_id', 'production_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_allocations');
        Schema::dropIfExists('production_plans');
    }
};

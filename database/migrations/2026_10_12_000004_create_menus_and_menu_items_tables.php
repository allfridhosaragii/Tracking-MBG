<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-03: Menus and Menu Items tables conforming to DATA-DICTIONARY.md
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->restrictOnDelete();
            $table->string('menu_code', 40);
            $table->string('name', 180);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('DRAFT'); // DRAFT, ACTIVE, ARCHIVED
            $table->string('image_path', 400)->nullable();
            $table->date('effective_from')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            // Uniqueness per SPPG, menu code, and version
            $table->unique(['sppg_id', 'menu_code', 'version'], 'menus_sppg_code_version_unique');
            $table->index(['sppg_id', 'status'], 'menus_sppg_status_idx');
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->string('item_name', 180);
            $table->decimal('serving_weight_g', 9, 2);
            $table->decimal('energy_kcal', 10, 2)->nullable();
            $table->decimal('protein_g', 10, 2)->nullable();
            $table->decimal('carbohydrate_g', 10, 2)->nullable();
            $table->decimal('fat_g', 10, 2)->nullable();
            $table->decimal('fiber_g', 10, 2)->nullable();
            $table->string('nutrition_source', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('menu_id', 'menu_items_menu_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};

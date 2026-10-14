<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows rejected_portions to be nullable when attribution is unknown during multi-batch discrepancies.
     */
    public function up(): void
    {
        Schema::table('quality_checks', function (Blueprint $table) {
            $table->unsignedInteger('rejected_portions')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quality_checks', function (Blueprint $table) {
            $table->unsignedInteger('rejected_portions')->nullable(false)->default(0)->change();
        });
    }
};

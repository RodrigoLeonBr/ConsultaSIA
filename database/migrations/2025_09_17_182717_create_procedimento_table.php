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
        Schema::create('procedimento', function (Blueprint $table) {
            $table->string('codigo', 10)->primary();
            $table->string('procedimento', 63)->default('');
            $table->decimal('pa_total', 12, 2)->default(0.00);
            $table->string('rub_total', 4)->default('');
            $table->string('rub_dc', 40)->default('');
            $table->string('pa_rub', 4)->default('');
            $table->string('pa_id', 9);
            $table->string('financiamento', 60)->nullable();
            
            // Index as specified in MySQL
            $table->index('pa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimento');
    }
};

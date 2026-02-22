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
        Schema::create('s_rub', function (Blueprint $table) {
            $table->char('rub_id', 4)->primary();
            $table->char('rub_dc', 40)->default('');
            $table->char('rub_total', 2)->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_rub');
    }
};

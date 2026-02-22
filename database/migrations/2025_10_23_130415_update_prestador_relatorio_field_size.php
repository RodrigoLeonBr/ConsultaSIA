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
        Schema::table('prestador', function (Blueprint $table) {
            $table->string('relatorio', 40)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestador', function (Blueprint $table) {
            $table->string('relatorio', 12)->nullable()->change();
        });
    }
};

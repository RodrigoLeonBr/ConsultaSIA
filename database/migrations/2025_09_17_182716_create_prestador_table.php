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
        Schema::create('prestador', function (Blueprint $table) {
            $table->string('re_cunid', 7)->primary();
            $table->string('re_cnome', 35);
            $table->char('re_tipo', 1);
            $table->string('cnpj', 14)->nullable();
            $table->integer('area');
            $table->char('tipouni', 1);
            $table->string('relatorio', 12)->nullable();
            $table->boolean('ativo')->default(1);
            
            // Indexes as specified in MySQL
            $table->index('cnpj');
            $table->index('ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestador');
    }
};

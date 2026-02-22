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
        Schema::create('forma', function (Blueprint $table) {
            $table->integer('id_registro')->primary();
            $table->string('grupo', 2);
            $table->string('subgrupo', 4);
            $table->string('forma', 6);
            $table->string('descricao', 100);
            
            // Índices para performance
            $table->index('grupo');
            $table->index('subgrupo');
            $table->index('forma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forma');
    }
};

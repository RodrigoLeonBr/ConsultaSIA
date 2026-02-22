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
        Schema::create('cismetro', function (Blueprint $table) {
            $table->id(); // Campo chave autoincremento
            $table->string('codigo', 11);
            $table->string('credenciamento', 40);
            $table->string('grupo', 40);
            $table->string('descricao', 180);
            $table->decimal('valor', 15, 2);
            $table->timestamps();
            
            // Índices para performance
            $table->index('codigo');
            $table->index('credenciamento');
            $table->index('grupo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cismetro');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sus_paulista', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 11);
            $table->enum('modalidade', ['sia', 'sih']);
            $table->char('competencia_inicial', 6);
            $table->char('competencia_final', 6)->default('999999');
            $table->string('descricao', 180)->nullable();
            $table->decimal('tab_paulista', 15, 2);
            $table->decimal('complementacao_tsp', 15, 2);
            $table->timestamps();

            $table->unique(['codigo', 'modalidade', 'competencia_inicial'], 'sus_paulista_cod_mod_comp_unique');
            $table->index(['modalidade', 'competencia_inicial', 'competencia_final'], 'sus_paulista_vigencia_index');
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sus_paulista');
    }
};

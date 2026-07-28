<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Produção e-SUS por procedimento SIGTAP (já agregada na origem).
        Schema::create('s_esus', function (Blueprint $table) {
            $table->id();
            $table->string('competencia', 7); // YYYY-MM (formato nativo e-SUS)
            $table->string('cnes', 7)->nullable();
            $table->string('unidade', 180);
            $table->string('tipo_relatorio', 60)->nullable();
            $table->string('bloco', 120)->nullable();
            $table->string('descricao_esus', 180)->nullable();
            $table->string('codigo_sigtap', 10);
            $table->string('descricao_sigtap', 180)->nullable();
            $table->integer('quantidade')->default(0);
            $table->timestamps();

            $table->unique(
                ['competencia', 'unidade', 'codigo_sigtap', 'descricao_esus'],
                'uk_esus'
            );
            $table->index('competencia', 'idx_esus_cmp');
            $table->index('cnes', 'idx_esus_cnes');
            $table->index(['cnes', 'competencia'], 'idx_esus_cnes_cmp');
            $table->index('codigo_sigtap', 'idx_esus_sigtap');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('s_esus');
    }
};

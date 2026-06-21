<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('s_aih', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('AIH', 13)->notNull();
            $table->string('CNES', 7)->notNull();
            $table->string('COMPETENCIA', 6)->notNull();
            $table->string('ESPECIALIDADE', 3)->nullable();
            $table->string('PROC_PRINCIPAL', 10)->nullable();
            $table->string('DIAG_PRINCIPAL', 4)->nullable();
            $table->string('COMPLEXIDADE', 2)->nullable();
            $table->string('FINANCIAMENTO', 2)->nullable();
            $table->string('ENFERMARIA', 4)->nullable();
            $table->string('MOTIVO_SAIDA', 2)->nullable();
            $table->string('SEXO_PACIENTE', 1)->nullable();
            $table->integer('DIARIAS')->nullable();
            $table->integer('DIARIAS_UTI')->nullable();

            $table->unique(['AIH', 'CNES', 'COMPETENCIA'], 'uk_aih');
            $table->index('CNES', 'idx_aih_cnes');
            $table->index('COMPETENCIA', 'idx_aih_cmp');
            $table->index(['CNES', 'COMPETENCIA'], 'idx_aih_cnes_cmp');
        });

        Schema::create('s_aih_pa', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('AIH', 13)->notNull();
            $table->string('CNES', 7)->notNull();
            $table->string('COMPETENCIA', 6)->notNull();
            $table->string('PROC_DETALHADO', 10)->nullable();
            $table->integer('QUANTIDADE')->nullable();
            $table->decimal('VALOR_ITEM', 12, 2)->nullable();
            $table->string('FINANCIAMENTO_DETALHE', 2)->nullable();
            $table->string('CBO_PROFISSIONAL', 6)->nullable();

            $table->index('AIH', 'idx_aih_pa_aih');
            $table->index('CNES', 'idx_aih_pa_cnes');
            $table->index('COMPETENCIA', 'idx_aih_pa_cmp');
            $table->index(['CNES', 'COMPETENCIA'], 'idx_aih_pa_cnes_cmp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('s_aih_pa');
        Schema::dropIfExists('s_aih');
    }
};

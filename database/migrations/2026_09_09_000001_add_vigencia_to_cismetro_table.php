<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cismetro', function (Blueprint $table) {
            // Vigência da tabela de valores (mesma semântica de sus_paulista).
            $table->char('competencia_inicial', 6)->default('202301')->after('tipo_valor');
            $table->char('competencia_final', 6)->default('999999')->after('competencia_inicial');

            // ponytail: índice não-único; legado tem 3 pares (codigo,descricao) duplicados.
            // Dedupe por (codigo, descricao, competencia_inicial) é feito no import, não no DB.
            $table->index(['competencia_inicial', 'competencia_final'], 'cismetro_vigencia_index');
        });

        // Backfill: dados atuais são "Credencimento 2023".
        DB::table('cismetro')->update([
            'competencia_inicial' => '202301',
            'competencia_final' => '999999',
        ]);
    }

    public function down(): void
    {
        Schema::table('cismetro', function (Blueprint $table) {
            $table->dropIndex('cismetro_vigencia_index');
            $table->dropColumn(['competencia_inicial', 'competencia_final']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('s_esus', function (Blueprint $table) {
            $table->dropUnique('uk_esus');
            $table->unique(
                ['competencia', 'unidade', 'tipo_relatorio', 'bloco', 'codigo_sigtap', 'descricao_esus'],
                'uk_esus'
            );
        });
    }

    public function down(): void
    {
        Schema::table('s_esus', function (Blueprint $table) {
            $table->dropUnique('uk_esus');
            $table->unique(
                ['competencia', 'unidade', 'codigo_sigtap', 'descricao_esus'],
                'uk_esus'
            );
        });
    }
};

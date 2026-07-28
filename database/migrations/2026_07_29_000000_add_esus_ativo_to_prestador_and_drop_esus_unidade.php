<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flag: unidade é considerada nas consultas/relatórios e-SUS (padrão sim).
        if (! Schema::hasColumn('prestador', 'esus_ativo')) {
            Schema::table('prestador', function (Blueprint $table) {
                $table->boolean('esus_ativo')->default(1)->after('ativo');
                $table->index('esus_ativo');
            });
        }

        // De-para nome->CNES descontinuado (CNES vem no payload).
        Schema::dropIfExists('esus_unidade');
    }

    public function down(): void
    {
        if (Schema::hasColumn('prestador', 'esus_ativo')) {
            Schema::table('prestador', function (Blueprint $table) {
                $table->dropIndex(['esus_ativo']);
                $table->dropColumn('esus_ativo');
            });
        }
    }
};

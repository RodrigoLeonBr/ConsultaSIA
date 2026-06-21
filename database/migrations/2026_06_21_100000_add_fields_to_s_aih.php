<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('s_aih', function (Blueprint $table) {
            $table->string('DT_NASC', 8)->nullable()->after('COMPETENCIA');
            $table->integer('IDADE')->nullable()->after('DT_NASC');
            $table->string('DT_INT', 8)->nullable()->after('SEXO_PACIENTE');
            $table->string('DT_SAIDA', 8)->nullable()->after('DT_INT');
            $table->decimal('VALOR_TOTAL_AIH', 12, 2)->nullable()->after('DIARIAS_UTI');
        });
    }

    public function down(): void
    {
        Schema::table('s_aih', function (Blueprint $table) {
            $table->dropColumn(['DT_NASC', 'IDADE', 'DT_INT', 'DT_SAIDA', 'VALOR_TOTAL_AIH']);
        });
    }
};

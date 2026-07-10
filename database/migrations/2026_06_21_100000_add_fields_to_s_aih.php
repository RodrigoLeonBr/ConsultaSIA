<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('s_aih')) {
            return;
        }

        Schema::table('s_aih', function (Blueprint $table) {
            if (! Schema::hasColumn('s_aih', 'DT_NASC')) {
                $table->string('DT_NASC', 8)->nullable()->after('COMPETENCIA');
            }
            if (! Schema::hasColumn('s_aih', 'IDADE')) {
                $table->integer('IDADE')->nullable()->after('DT_NASC');
            }
            if (! Schema::hasColumn('s_aih', 'DT_INT')) {
                $table->string('DT_INT', 8)->nullable()->after('SEXO_PACIENTE');
            }
            if (! Schema::hasColumn('s_aih', 'DT_SAIDA')) {
                $table->string('DT_SAIDA', 8)->nullable()->after('DT_INT');
            }
            if (! Schema::hasColumn('s_aih', 'VALOR_TOTAL_AIH')) {
                $table->decimal('VALOR_TOTAL_AIH', 12, 2)->nullable()->after('DIARIAS_UTI');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('s_aih')) {
            return;
        }

        Schema::table('s_aih', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('s_aih', 'DT_NASC') ? 'DT_NASC' : null,
                Schema::hasColumn('s_aih', 'IDADE') ? 'IDADE' : null,
                Schema::hasColumn('s_aih', 'DT_INT') ? 'DT_INT' : null,
                Schema::hasColumn('s_aih', 'DT_SAIDA') ? 'DT_SAIDA' : null,
                Schema::hasColumn('s_aih', 'VALOR_TOTAL_AIH') ? 'VALOR_TOTAL_AIH' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

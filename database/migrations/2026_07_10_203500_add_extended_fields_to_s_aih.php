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
            if (! Schema::hasColumn('s_aih', 'IDENT_AIH')) {
                $table->string('IDENT_AIH', 2)->nullable()->after('AIH');
            }
            if (! Schema::hasColumn('s_aih', 'MUN_RESIDENCIA')) {
                $table->string('MUN_RESIDENCIA', 6)->nullable()->after('COMPETENCIA');
            }
            if (! Schema::hasColumn('s_aih', 'CARATER_INTERNACAO')) {
                $table->string('CARATER_INTERNACAO', 2)->nullable()->after('DT_SAIDA');
            }
            if (! Schema::hasColumn('s_aih', 'DIAG_SECUNDARIO')) {
                $table->string('DIAG_SECUNDARIO', 4)->nullable()->after('DIAG_PRINCIPAL');
            }
            if (! Schema::hasColumn('s_aih', 'CID_OBITO')) {
                $table->string('CID_OBITO', 4)->nullable()->after('MOTIVO_SAIDA');
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
                Schema::hasColumn('s_aih', 'IDENT_AIH') ? 'IDENT_AIH' : null,
                Schema::hasColumn('s_aih', 'MUN_RESIDENCIA') ? 'MUN_RESIDENCIA' : null,
                Schema::hasColumn('s_aih', 'CARATER_INTERNACAO') ? 'CARATER_INTERNACAO' : null,
                Schema::hasColumn('s_aih', 'DIAG_SECUNDARIO') ? 'DIAG_SECUNDARIO' : null,
                Schema::hasColumn('s_aih', 'CID_OBITO') ? 'CID_OBITO' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

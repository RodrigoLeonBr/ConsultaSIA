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
        Schema::create('s_prd', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('prd_uid', 7);
            $table->string('prd_cmp', 6);
            $table->char('prd_flh', 3);
            $table->char('prd_seq', 2);
            $table->string('prd_pa', 10);
            $table->string('prd_cbo', 8);
            $table->integer('prd_idade', false, true)->nullable();
            $table->integer('prd_qt_p', false, true)->nullable();
            $table->integer('prd_qt_a', false, true)->nullable();
            $table->decimal('prd_vl_p', 15, 2)->nullable();
            $table->decimal('prd_vl_a', 15, 2)->nullable();
            $table->string('prd_mvm', 6)->default('');
            $table->char('prd_org', 3)->default('');
            $table->char('prd_flpa', 1)->default('');
            $table->char('prd_flcbo', 1)->default('');
            $table->char('prd_flca', 1)->default('');
            $table->char('prd_flida', 1)->default('');
            $table->char('prd_flqt', 1)->default('');
            $table->char('prd_fler', 1)->default('');
            $table->string('prd_apanum', 13)->default('');
            $table->string('prd_cnsmed', 15)->nullable();
            $table->string('prd_rms', 4)->default('');
            $table->string('prd_cnpj', 14)->default('');
            $table->string('prd_nfis', 6)->default('');
            $table->string('prd_resid', 6)->default('');
            $table->string('prd_rub', 6)->default('');
            $table->char('prd_cpx', 1)->default('');
            $table->char('prd_tpfin', 1)->default('');
            $table->integer('prd_qtdatr', false, true)->nullable();
            $table->integer('prd_qtdatu', false, true)->nullable();
            $table->string('prd_rc', 4)->default('');
            $table->string('prd_cidpri', 6)->default('');
            $table->string('prd_cidsec', 6)->default('');
            $table->string('prd_cidcas', 6)->default('');
            $table->string('prd_incout', 4)->default('');
            $table->string('prd_incurg', 4)->default('');
            
            // Generated columns (using SQLite compatible SUBSTR function)
            $table->string('grupo', 2)->virtualAs('substr(prd_pa, 1, 2)');
            $table->string('subgrupo', 4)->virtualAs('substr(prd_pa, 1, 4)'); 
            $table->string('forma', 6)->virtualAs('substr(prd_pa, 1, 6)');
            
            // Indexes as specified in MySQL
            $table->index(['prd_uid', 'prd_cmp', 'prd_flh', 'prd_seq'], 'idx_composite');
            $table->index('prd_uid');
            $table->index('prd_cmp');
            $table->index('prd_pa');
            $table->index('prd_cbo');
            $table->index('grupo');
            $table->index('subgrupo');
            $table->index('forma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_prd');
    }
};

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
        Schema::create('s_pap', function (Blueprint $table) {
            $table->string('PAP_UID', 7)->nullable();
            $table->string('PAP_CMP', 6)->nullable();
            $table->string('PAP_NUM', 13)->nullable();
            $table->string('PAP_PA', 10)->nullable();
            $table->string('PAP_SEQ', 2)->nullable();
            $table->string('PAP_CBO', 6)->nullable();
            $table->smallInteger('PAP_IDADE')->nullable();
            $table->double('PAP_QT_P')->nullable();
            $table->double('PAP_QT_A')->nullable();
            $table->string('PAP_MVM', 6)->nullable();
            $table->string('PAP_ORG', 3)->nullable();
            $table->string('PAP_FLPA', 1)->nullable();
            $table->string('PAP_FLEMA', 1)->nullable();
            $table->string('PAP_FLCBO', 1)->nullable();
            $table->string('PAP_FLQT', 1)->nullable();
            $table->string('PAP_FLER', 1)->nullable();
            $table->string('PAP_CNPJ', 14)->nullable();
            $table->string('PAP_NFISC', 6)->nullable();
            $table->string('PAP_CIDPRI', 6)->nullable();
            $table->string('PAP_CIDSEC', 6)->nullable();
            $table->string('PAP_EQUIPE', 12)->nullable();
            $table->double('PAP_VL_FED')->nullable();
            $table->double('PAP_VL_LOC')->nullable();
            $table->double('PAP_VL_INC')->nullable();
            $table->string('PAP_INCOUT', 4)->nullable();
            $table->string('PAP_INCURG', 4)->nullable();
            $table->string('PAP_RUB', 6)->nullable();
            $table->string('PAP_TPFIN', 1)->nullable();
            $table->string('PAP_CPX', 1)->nullable();
            $table->string('PAP_RC', 4)->nullable();
            $table->string('PAP_UNTERC', 7)->nullable();
            
            // Índices para performance
            $table->index(['PAP_UID', 'PAP_CMP', 'PAP_NUM'], 'idx_pap_composite');
            $table->index('PAP_NUM');
            $table->index('PAP_UID');
            $table->index('PAP_PA');
            $table->index('PAP_CBO');
            $table->index('PAP_MVM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_pap');
    }
};
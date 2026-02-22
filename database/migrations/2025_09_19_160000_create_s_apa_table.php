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
        Schema::create('s_apa', function (Blueprint $table) {
            $table->string('APA_UID', 7)->nullable();
            $table->string('APA_NUM', 13)->nullable();
            $table->string('APA_EMISSA', 8)->nullable();
            $table->string('APA_DTINIC', 8)->nullable();
            $table->string('APA_DTFIM', 8)->nullable();
            $table->string('APA_TPATEN', 2)->nullable();
            $table->string('APA_TPAPAC', 1)->nullable();
            $table->string('APA_NMPCN', 30)->nullable();
            $table->string('APA_UFPCN', 3)->nullable();
            $table->string('APA_MAEPCN', 30)->nullable();
            $table->string('APA_LOGPCN', 30)->nullable();
            $table->string('APA_NUMPCN', 5)->nullable();
            $table->string('APA_CPLPCN', 10)->nullable();
            $table->string('APA_CEPPCN', 8)->nullable();
            $table->string('APA_MUNPCN', 7)->nullable();
            $table->string('APA_DTNASC', 8)->nullable();
            $table->string('APA_SEXPCN', 1)->nullable();
            $table->string('APA_VARIA', 141)->nullable();
            $table->string('APA_CPFRES', 11)->nullable();
            $table->string('APA_NMRES', 30)->nullable();
            $table->string('APA_MOTCOB', 2)->nullable();
            $table->string('APA_DTOBAL', 8)->nullable();
            $table->string('APA_CPFDIR', 11)->nullable();
            $table->string('APA_NMDIR', 30)->nullable();
            $table->string('APA_CMP', 6)->nullable();
            $table->string('APA_MVM', 6)->nullable();
            $table->string('APA_RMS', 4)->nullable();
            $table->string('APA_DTGER', 8)->nullable();
            $table->string('APA_FLER', 10)->nullable();
            $table->string('APA_INERPP', 1)->nullable();
            $table->string('APA_PRIPAL', 9)->nullable();
            $table->string('APA_CPFPCT', 11)->nullable();
            $table->string('APA_CNSPCT', 15)->nullable();
            $table->string('APA_CNSRES', 15)->nullable();
            $table->string('APA_CNSDIR', 15)->nullable();
            $table->string('APA_CIDCA', 4)->nullable();
            $table->string('APA_NPRONT', 10)->nullable();
            $table->string('APA_CODSOL', 7)->nullable();
            $table->string('APA_DTSOL', 8)->nullable();
            $table->string('APA_DTAUT', 8)->nullable();
            $table->string('APA_CODEMI', 10)->nullable();
            $table->string('APA_CATEND', 2)->nullable();
            $table->string('APA_APACAN', 14)->nullable();
            $table->string('APA_RACA', 2)->nullable();
            $table->string('APA_NOMERE', 30)->nullable();
            $table->string('APA_ETNIA', 4)->nullable();
            $table->string('APA_ADVLMC', 1)->nullable();
            $table->string('APA_ADVTZM', 1)->nullable();
            $table->string('APA_SRV', 3)->nullable();
            $table->string('APA_CSF', 3)->nullable();
            $table->string('APA_CDLOGR', 3)->nullable();
            $table->string('APA_BAIRRO', 30)->nullable();
            $table->string('APA_DDD', 2)->nullable();
            $table->string('APA_TEL', 9)->nullable();
            $table->string('APA_EMAIL', 40)->nullable();
            $table->string('APA_CNSEXE', 15)->nullable();
            $table->string('APA_INE', 10)->nullable();
            $table->string('APA_ADVSEX', 1)->nullable();
            $table->string('APA_EXPMAE', 1)->nullable();
            $table->string('APA_STRUA', 1)->nullable();
            
            // Índices para performance
            $table->index('APA_NUM');
            $table->index('APA_UID');
            $table->index('APA_PRIPAL');
            $table->index('APA_MVM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_apa');
    }
};
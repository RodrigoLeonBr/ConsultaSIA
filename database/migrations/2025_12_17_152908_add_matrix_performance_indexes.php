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
        Schema::table('s_prd', function (Blueprint $table) {
            // Índice composto para queries de matriz (competência + campos principais)
            $table->index(['prd_cmp', 'prd_uid', 'prd_pa'], 'idx_s_prd_matrix_main');
            
            // Índice específico para competência (usado em filtros e ordenação)
            $table->index(['prd_cmp'], 'idx_s_prd_competencia');
            
            // Índice para agregações por prestador e competência
            $table->index(['prd_uid', 'prd_cmp'], 'idx_s_prd_prestador_comp');
            
            // Índice para agregações por procedimento e competência
            $table->index(['prd_pa', 'prd_cmp'], 'idx_s_prd_procedimento_comp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('s_prd', function (Blueprint $table) {
            $table->dropIndex('idx_s_prd_matrix_main');
            $table->dropIndex('idx_s_prd_competencia');
            $table->dropIndex('idx_s_prd_prestador_comp');
            $table->dropIndex('idx_s_prd_procedimento_comp');
        });
    }
};

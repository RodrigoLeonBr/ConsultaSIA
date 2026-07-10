<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('s_aih', function (Blueprint $table) {
            $table->dropUnique('uk_aih');
            $table->unique(['AIH', 'CNES', 'COMPETENCIA', 'DT_SAIDA'], 'uk_aih');
        });
    }

    public function down(): void
    {
        Schema::table('s_aih', function (Blueprint $table) {
            $table->dropUnique('uk_aih');
            $table->unique(['AIH', 'CNES', 'COMPETENCIA'], 'uk_aih');
        });
    }
};

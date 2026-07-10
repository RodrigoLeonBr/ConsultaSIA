<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('s_aih') || ! Schema::hasColumn('s_aih', 'DT_SAIDA')) {
            return;
        }

        if ($this->uniqueKeyIncludesDtSaida()) {
            return;
        }

        Schema::table('s_aih', function (Blueprint $table) {
            $table->dropUnique('uk_aih');
            $table->unique(['AIH', 'CNES', 'COMPETENCIA', 'DT_SAIDA'], 'uk_aih');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('s_aih')) {
            return;
        }

        if (! $this->uniqueKeyIncludesDtSaida()) {
            return;
        }

        Schema::table('s_aih', function (Blueprint $table) {
            $table->dropUnique('uk_aih');
            $table->unique(['AIH', 'CNES', 'COMPETENCIA'], 'uk_aih');
        });
    }

    private function uniqueKeyIncludesDtSaida(): bool
    {
        $indexes = DB::select("SHOW INDEX FROM s_aih WHERE Key_name = 'uk_aih'");

        if ($indexes === []) {
            return false;
        }

        $columns = collect($indexes)
            ->sortBy('Seq_in_index')
            ->pluck('Column_name')
            ->values()
            ->all();

        return $columns === ['AIH', 'CNES', 'COMPETENCIA', 'DT_SAIDA'];
    }
};

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SihAihMigrationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function expectedExtendedColumns(): array
    {
        return [
            'IDENT_AIH',
            'MUN_RESIDENCIA',
            'CARATER_INTERNACAO',
            'DIAG_SECUNDARIO',
            'CID_OBITO',
        ];
    }

    public function test_s_aih_has_extended_columns_after_full_migration_chain(): void
    {
        foreach ($this->expectedExtendedColumns() as $column) {
            $this->assertTrue(
                Schema::hasColumn('s_aih', $column),
                "Coluna ausente após migrations: {$column}",
            );
        }
    }

    public function test_extended_fields_migration_upgrades_legacy_s_aih_table(): void
    {
        Schema::table('s_aih', function ($table) {
            $legacyColumns = array_filter(
                $this->expectedExtendedColumns(),
                fn (string $column) => Schema::hasColumn('s_aih', $column),
            );

            if ($legacyColumns !== []) {
                $table->dropColumn(array_values($legacyColumns));
            }
        });

        foreach ($this->expectedExtendedColumns() as $column) {
            $this->assertFalse(Schema::hasColumn('s_aih', $column));
        }

        $migration = include database_path('migrations/2026_07_10_203500_add_extended_fields_to_s_aih.php');
        $migration->up();

        foreach ($this->expectedExtendedColumns() as $column) {
            $this->assertTrue(
                Schema::hasColumn('s_aih', $column),
                "Coluna não criada pela migration incremental: {$column}",
            );
        }
    }
}

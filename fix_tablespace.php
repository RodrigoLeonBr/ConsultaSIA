<?php

// Script para corrigir tablespaces órfãos no MySQL
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Corrigindo tablespaces órfãos...\n";

    // Listar tablespaces órfãos
    $result = DB::select("
        SELECT NAME FROM INFORMATION_SCHEMA.INNODB_TABLESPACES
        WHERE NAME LIKE 'consultaprod/%'
        AND NAME NOT IN (
            SELECT CONCAT(TABLE_SCHEMA, '/', TABLE_NAME)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = 'consultaprod'
        )
    ");

    foreach ($result as $tablespace) {
        try {
            echo "Removendo tablespace órfão: {$tablespace->NAME}\n";
            DB::statement("DROP TABLESPACE `{$tablespace->NAME}`");
        } catch (Exception $e) {
            echo "Erro ao remover tablespace {$tablespace->NAME}: " . $e->getMessage() . "\n";
        }
    }

    // Tentar remover arquivos .ibd órfãos diretamente
    $tables = ['migrations', 'users', 'cbo', 'procedimento', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens'];

    foreach ($tables as $table) {
        try {
            DB::statement("ALTER TABLE `consultaprod`.`$table` DISCARD TABLESPACE");
            echo "Tablespace da tabela $table descartado.\n";
        } catch (Exception $e) {
            // Ignorar erros se a tabela não existir
        }
    }

    echo "Correção de tablespaces concluída.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

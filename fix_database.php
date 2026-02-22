<?php

// Script para corrigir problemas de tablespace
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Iniciando correção do banco de dados...\n";

    // Desabilitar verificação de chaves estrangeiras
    DB::statement('SET foreign_key_checks = 0');

    // Dropar tabelas problemáticas
    $tables = ['migrations', 'users', 'cbo', 'procedimento', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens'];

    foreach ($tables as $table) {
        try {
            DB::statement("DROP TABLE IF EXISTS `$table`");
            echo "Tabela $table removida.\n";
        } catch (Exception $e) {
            echo "Erro ao remover tabela $table: " . $e->getMessage() . "\n";
        }
    }

    // Reabilitar verificação de chaves estrangeiras
    DB::statement('SET foreign_key_checks = 1');

    echo "Limpeza concluída. Execute agora: php artisan migrate\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Descartando tablespaces órfãos...\n";

    $tables = ['cbo', 'procedimento', 'users'];

    foreach ($tables as $table) {
        try {
            // Tentar descartar tablespace
            DB::statement("ALTER TABLE `consultaprod`.`$table` DISCARD TABLESPACE");
            echo "Tablespace da tabela $table descartado.\n";
        } catch (Exception $e) {
            echo "Erro ao descartar tablespace de $table (pode não existir): " . $e->getMessage() . "\n";
        }

        try {
            // Dropar tabela
            DB::statement("DROP TABLE IF EXISTS `$table`");
            echo "Tabela $table removida.\n";
        } catch (Exception $e) {
            echo "Erro ao remover tabela $table: " . $e->getMessage() . "\n";
        }
    }

    // Agora tentar criar as tabelas
    echo "\nCriando tabelas...\n";

    // Criar tabela CBO
    DB::statement("
        CREATE TABLE `cbo` (
            `cbo` varchar(6) NOT NULL,
            `ds_cbo` varchar(120) NOT NULL,
            PRIMARY KEY (`cbo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela CBO criada.\n";

    // Criar tabela procedimento
    DB::statement("
        CREATE TABLE `procedimento` (
            `codigo` varchar(10) NOT NULL,
            `procedimento` varchar(63) NOT NULL DEFAULT '',
            `pa_total` decimal(12,2) NOT NULL DEFAULT '0.00',
            `rub_total` varchar(4) NOT NULL DEFAULT '',
            `rub_dc` varchar(40) NOT NULL DEFAULT '',
            `pa_rub` varchar(4) NOT NULL DEFAULT '',
            `pa_id` varchar(9) NOT NULL,
            `financiamento` varchar(60) DEFAULT NULL,
            PRIMARY KEY (`codigo`),
            KEY `pa_id` (`pa_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela procedimento criada.\n";

    // Criar tabela users
    DB::statement("
        CREATE TABLE `users` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `email_verified_at` timestamp NULL DEFAULT NULL,
            `password` varchar(255) NOT NULL,
            `role` enum('admin','operator') NOT NULL DEFAULT 'operator',
            `active` tinyint(1) NOT NULL DEFAULT '1',
            `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
            `remember_token` varchar(100) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela users criada.\n";

    echo "\nTodas as tabelas foram criadas com sucesso!\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

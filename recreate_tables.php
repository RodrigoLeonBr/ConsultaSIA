<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Recriando tabelas necessárias...\n";

    // Desabilitar verificação de chaves estrangeiras
    DB::statement('SET foreign_key_checks = 0');

    // Dropar e recriar tabela CBO
    DB::statement("DROP TABLE IF EXISTS `cbo`");
    DB::statement("
        CREATE TABLE `cbo` (
            `cbo` varchar(6) NOT NULL,
            `ds_cbo` varchar(120) NOT NULL,
            PRIMARY KEY (`cbo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela CBO recriada.\n";

    // Dropar e recriar tabela procedimento
    DB::statement("DROP TABLE IF EXISTS `procedimento`");
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
    echo "Tabela procedimento recriada.\n";

    // Dropar e recriar tabela users
    DB::statement("DROP TABLE IF EXISTS `users`");
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
    echo "Tabela users recriada.\n";

    // Reabilitar verificação de chaves estrangeiras
    DB::statement('SET foreign_key_checks = 1');

    echo "Todas as tabelas foram recriadas com sucesso!\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

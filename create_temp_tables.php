<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Criando tabelas com nomes temporários...\n";

    // Criar tabela CBO temporária
    DB::statement("DROP TABLE IF EXISTS `cbo_temp`");
    DB::statement("
        CREATE TABLE `cbo_temp` (
            `cbo` varchar(6) NOT NULL,
            `ds_cbo` varchar(120) NOT NULL,
            PRIMARY KEY (`cbo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabela cbo_temp criada.\n";

    // Renomear para cbo
    DB::statement("RENAME TABLE `cbo_temp` TO `cbo`");
    echo "Tabela renomeada para cbo.\n";

    // Criar tabela procedimento temporária
    DB::statement("DROP TABLE IF EXISTS `procedimento_temp`");
    DB::statement("
        CREATE TABLE `procedimento_temp` (
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
    echo "Tabela procedimento_temp criada.\n";

    // Renomear para procedimento
    DB::statement("RENAME TABLE `procedimento_temp` TO `procedimento`");
    echo "Tabela renomeada para procedimento.\n";

    // Criar tabela users temporária
    DB::statement("DROP TABLE IF EXISTS `users_temp`");
    DB::statement("
        CREATE TABLE `users_temp` (
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
    echo "Tabela users_temp criada.\n";

    // Renomear para users
    DB::statement("RENAME TABLE `users_temp` TO `users`");
    echo "Tabela renomeada para users.\n";

    echo "\nTodas as tabelas foram criadas com sucesso!\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
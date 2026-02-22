-- Script para corrigir problemas de tablespace no MySQL
-- Execute este script diretamente no MySQL

USE consultaprod;

-- Remover tablespaces órfãos se existirem
SET foreign_key_checks = 0;

-- Dropar tabelas se existirem
DROP TABLE IF EXISTS migrations;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS cbo;
DROP TABLE IF EXISTS procedimento;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS job_batches;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS password_reset_tokens;

SET foreign_key_checks = 1;

-- Criar tabela de migrações
CREATE TABLE migrations (
    id int unsigned NOT NULL AUTO_INCREMENT,
    migration varchar(255) NOT NULL,
    batch int NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de usuários
CREATE TABLE users (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    email_verified_at timestamp NULL DEFAULT NULL,
    password varchar(255) NOT NULL,
    role enum('admin','operator') NOT NULL DEFAULT 'operator',
    active tinyint(1) NOT NULL DEFAULT '1',
    must_change_password tinyint(1) NOT NULL DEFAULT '1',
    remember_token varchar(100) DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela CBO
CREATE TABLE cbo (
    cbo varchar(6) NOT NULL,
    ds_cbo varchar(120) NOT NULL,
    PRIMARY KEY (cbo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela procedimento
CREATE TABLE procedimento (
    codigo varchar(10) NOT NULL,
    procedimento varchar(63) NOT NULL DEFAULT '',
    pa_total decimal(12,2) NOT NULL DEFAULT '0.00',
    rub_total varchar(4) NOT NULL DEFAULT '',
    rub_dc varchar(40) NOT NULL DEFAULT '',
    pa_rub varchar(4) NOT NULL DEFAULT '',
    pa_id varchar(9) NOT NULL,
    financiamento varchar(60) DEFAULT NULL,
    PRIMARY KEY (codigo),
    KEY pa_id (pa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabelas auxiliares do Laravel
CREATE TABLE cache (
    `key` varchar(255) NOT NULL,
    value mediumtext NOT NULL,
    expiration int NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
    `key` varchar(255) NOT NULL,
    owner varchar(255) NOT NULL,
    expiration int NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    queue varchar(255) NOT NULL,
    payload longtext NOT NULL,
    attempts tinyint unsigned NOT NULL,
    reserved_at int unsigned DEFAULT NULL,
    available_at int unsigned NOT NULL,
    created_at int unsigned NOT NULL,
    PRIMARY KEY (id),
    KEY jobs_queue_index (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_batches (
    id varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    total_jobs int NOT NULL,
    pending_jobs int NOT NULL,
    failed_jobs int NOT NULL,
    failed_job_ids longtext NOT NULL,
    options mediumtext,
    cancelled_at int DEFAULT NULL,
    created_at int NOT NULL,
    finished_at int DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    uuid varchar(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload longtext NOT NULL,
    exception longtext NOT NULL,
    failed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY failed_jobs_uuid_unique (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
    id varchar(255) NOT NULL,
    user_id bigint unsigned DEFAULT NULL,
    ip_address varchar(45) DEFAULT NULL,
    user_agent text,
    payload longtext NOT NULL,
    last_activity int NOT NULL,
    PRIMARY KEY (id),
    KEY sessions_user_id_index (user_id),
    KEY sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    email varchar(255) NOT NULL,
    token varchar(255) NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registros de migração
INSERT INTO migrations (migration, batch) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2025_09_17_182713_create_cbo_table', 1),
('2025_09_17_182716_create_prestador_table', 1),
('2025_09_17_182717_create_procedimento_table', 1),
('2025_09_17_182718_create_s_rub_table', 1),
('2025_09_17_182719_create_s_prd_table', 1),
('2025_09_17_184500_add_must_change_password_to_users_table', 1);

SELECT 'Tabelas criadas com sucesso!' as status;

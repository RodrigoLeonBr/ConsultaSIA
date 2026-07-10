-- =============================================================================
-- ConsultaProd — Atualização do banco `producao` (XAMPP / MariaDB 10.4+)
-- Data: 2026-06-24
--
-- Objetivo:
--   Criar tabelas auxiliares novas (SUS Paulista, AIH, AIH-PA) e aplicar
--   alterações complementares sem tocar nas tabelas core DATASUS (s_prd, etc.).
--
-- Como executar:
--   1. Abra phpMyAdmin → banco `producao` → SQL
--   2. Cole e execute este arquivo inteiro
--   OU via linha de comando:
--      mysql -u root producao < database/sql/atualizar_producao_2026_06.sql
--
-- Seguro para reexecutar: usa IF NOT EXISTS e checagens em information_schema.
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

USE `producao`;

-- -----------------------------------------------------------------------------
-- 1) Tabela SUS Paulista (importação SIA/SIH + relatórios)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sus_paulista` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` varchar(11) NOT NULL,
  `modalidade` enum('sia','sih') NOT NULL,
  `competencia_inicial` char(6) NOT NULL,
  `competencia_final` char(6) NOT NULL DEFAULT '999999',
  `descricao` varchar(180) DEFAULT NULL,
  `tab_paulista` decimal(15,2) NOT NULL,
  `complementacao_tsp` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sus_paulista_cod_mod_comp_unique` (`codigo`,`modalidade`,`competencia_inicial`),
  KEY `sus_paulista_vigencia_index` (`modalidade`,`competencia_inicial`,`competencia_final`),
  KEY `sus_paulista_codigo_index` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Tabelas AIH (cabeçalho + procedimentos detalhados)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `s_aih` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `AIH` varchar(13) NOT NULL,
  `CNES` varchar(7) NOT NULL,
  `COMPETENCIA` varchar(6) NOT NULL,
  `DT_NASC` varchar(8) DEFAULT NULL,
  `IDADE` int(11) DEFAULT NULL,
  `ESPECIALIDADE` varchar(3) DEFAULT NULL,
  `PROC_PRINCIPAL` varchar(10) DEFAULT NULL,
  `DIAG_PRINCIPAL` varchar(4) DEFAULT NULL,
  `COMPLEXIDADE` varchar(2) DEFAULT NULL,
  `FINANCIAMENTO` varchar(2) DEFAULT NULL,
  `ENFERMARIA` varchar(4) DEFAULT NULL,
  `MOTIVO_SAIDA` varchar(2) DEFAULT NULL,
  `SEXO_PACIENTE` varchar(1) DEFAULT NULL,
  `DT_INT` varchar(8) DEFAULT NULL,
  `DT_SAIDA` varchar(8) DEFAULT NULL,
  `DIARIAS` int(11) DEFAULT NULL,
  `DIARIAS_UTI` int(11) DEFAULT NULL,
  `VALOR_TOTAL_AIH` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_aih` (`AIH`,`CNES`,`COMPETENCIA`,`DT_SAIDA`),
  KEY `idx_aih_cnes` (`CNES`),
  KEY `idx_aih_cmp` (`COMPETENCIA`),
  KEY `idx_aih_cnes_cmp` (`CNES`,`COMPETENCIA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `s_aih_pa` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `AIH` varchar(13) NOT NULL,
  `CNES` varchar(7) NOT NULL,
  `COMPETENCIA` varchar(6) NOT NULL,
  `PROC_DETALHADO` varchar(10) DEFAULT NULL,
  `QUANTIDADE` int(11) DEFAULT NULL,
  `VALOR_ITEM` decimal(12,2) DEFAULT NULL,
  `FINANCIAMENTO_DETALHE` varchar(2) DEFAULT NULL,
  `CBO_PROFISSIONAL` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aih_pa_aih` (`AIH`),
  KEY `idx_aih_pa_cnes` (`CNES`),
  KEY `idx_aih_pa_cmp` (`COMPETENCIA`),
  KEY `idx_aih_pa_cnes_cmp` (`CNES`,`COMPETENCIA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colunas extras em s_aih (se a tabela já existia na versão antiga sem esses campos)

SET @db := DATABASE();

-- DT_NASC
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'DT_NASC'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `DT_NASC` varchar(8) NULL AFTER `COMPETENCIA`',
  'SELECT ''s_aih.DT_NASC já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- IDADE
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'IDADE'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `IDADE` int(11) NULL AFTER `DT_NASC`',
  'SELECT ''s_aih.IDADE já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- DT_INT
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'DT_INT'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `DT_INT` varchar(8) NULL AFTER `SEXO_PACIENTE`',
  'SELECT ''s_aih.DT_INT já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- DT_SAIDA
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'DT_SAIDA'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `DT_SAIDA` varchar(8) NULL AFTER `DT_INT`',
  'SELECT ''s_aih.DT_SAIDA já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- VALOR_TOTAL_AIH
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'VALOR_TOTAL_AIH'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `VALOR_TOTAL_AIH` decimal(12,2) NULL AFTER `DIARIAS_UTI`',
  'SELECT ''s_aih.VALOR_TOTAL_AIH já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 3) Procedimento — colunas AIH (VL_SP, VL_SH) e descrição maior
--    Tabela auxiliar de catálogo; seguro alterar fora do contrato DATASUS de produção.
-- -----------------------------------------------------------------------------

-- Expandir descrição para 255 caracteres (se ainda for menor)
SET @col_len := (
  SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'procedimento' AND COLUMN_NAME = 'procedimento'
);
SET @sql := IF(@col_len IS NOT NULL AND @col_len < 255,
  'ALTER TABLE `procedimento` MODIFY COLUMN `procedimento` varchar(255) NOT NULL DEFAULT ''''',
  'SELECT ''procedimento.procedimento já é varchar(255)'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- VL_SP
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'procedimento' AND COLUMN_NAME = 'VL_SP'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `procedimento` ADD COLUMN `VL_SP` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `PA_TOTAL`',
  'SELECT ''procedimento.VL_SP já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- VL_SH
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'procedimento' AND COLUMN_NAME = 'VL_SH'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `procedimento` ADD COLUMN `VL_SH` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `VL_SP`',
  'SELECT ''procedimento.VL_SH já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 4) Registrar migrations no Laravel (opcional — só se a tabela existir)
-- -----------------------------------------------------------------------------

SET @migrations_exists := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations'
);

SET @batch := IF(
  @migrations_exists > 0,
  IFNULL((SELECT MAX(`batch`) FROM `migrations`), 0) + 1,
  1
);

-- Helper: insere migration se tabela migrations existir e registro ainda não existir
-- 2026_06_21_000000_create_s_aih_tables
SET @sql := IF(@migrations_exists > 0 AND (
  SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_06_21_000000_create_s_aih_tables'
) = 0,
  CONCAT('INSERT INTO `migrations` (`migration`, `batch`) VALUES (''2026_06_21_000000_create_s_aih_tables'', ', @batch, ')'),
  'SELECT ''migration s_aih já registrada ou tabela migrations ausente'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@migrations_exists > 0 AND (
  SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_06_21_100000_add_fields_to_s_aih'
) = 0,
  CONCAT('INSERT INTO `migrations` (`migration`, `batch`) VALUES (''2026_06_21_100000_add_fields_to_s_aih'', ', @batch, ')'),
  'SELECT ''migration s_aih fields já registrada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@migrations_exists > 0 AND (
  SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_06_22_152310_add_aih_values_to_procedimento_table'
) = 0,
  CONCAT('INSERT INTO `migrations` (`migration`, `batch`) VALUES (''2026_06_22_152310_add_aih_values_to_procedimento_table'', ', @batch, ')'),
  'SELECT ''migration procedimento AIH já registrada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@migrations_exists > 0 AND (
  SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_06_24_000001_create_sus_paulista_table'
) = 0,
  CONCAT('INSERT INTO `migrations` (`migration`, `batch`) VALUES (''2026_06_24_000001_create_sus_paulista_table'', ', @batch, ')'),
  'SELECT ''migration sus_paulista já registrada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 5) Verificação final
-- -----------------------------------------------------------------------------

SELECT 'sus_paulista' AS tabela, COUNT(*) AS registros FROM `sus_paulista`
UNION ALL
SELECT 's_aih', COUNT(*) FROM `s_aih`
UNION ALL
SELECT 's_aih_pa', COUNT(*) FROM `s_aih_pa`;

SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('sus_paulista', 's_aih', 's_aih_pa')
ORDER BY TABLE_NAME;

SELECT 'Atualização concluída com sucesso.' AS status;

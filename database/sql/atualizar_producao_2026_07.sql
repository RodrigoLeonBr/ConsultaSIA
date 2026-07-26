-- =============================================================================
-- ConsultaProd — Atualização do banco `producao` (XAMPP / MariaDB 10.4+)
-- Data: 2026-07-26
--
-- Objetivo:
--   Estender `s_aih` com campos SIHD (IDENT_AIH, MUN_RESIDENCIA, CARATER_INTERNACAO,
--   DIAG_SECUNDARIO, CID_OBITO) e atualizar a chave única uk_aih para incluir DT_SAIDA
--   (reabertura/fechamento de UTI na mesma competência).
--
-- Pré-requisito:
--   Já ter aplicado `database/sql/atualizar_producao_2026_06.sql` (ou as migrations
--   equivalentes que criam s_aih / s_aih_pa).
--
-- Como executar:
--   1. Abra phpMyAdmin → banco `producao` → SQL
--   2. Cole e execute este arquivo inteiro
--   OU via linha de comando:
--      mysql -u root producao < database/sql/atualizar_producao_2026_07.sql
--
-- Seguro para reexecutar: checa information_schema antes de cada ALTER.
-- Não altera tabelas core DATASUS (s_prd, s_apa, s_bpi, etc.).
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

USE `producao`;

SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1) Campos estendidos em s_aih (origem TB_HAIH / export SIHD — 23 colunas)
-- -----------------------------------------------------------------------------

-- IDENT_AIH (ah_ident)
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'IDENT_AIH'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `IDENT_AIH` varchar(2) NULL AFTER `AIH`',
  'SELECT ''s_aih.IDENT_AIH já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- MUN_RESIDENCIA (ah_paciente_logr_municipio / ah_mun_paci)
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'MUN_RESIDENCIA'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `MUN_RESIDENCIA` varchar(6) NULL AFTER `COMPETENCIA`',
  'SELECT ''s_aih.MUN_RESIDENCIA já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- CARATER_INTERNACAO (ah_car_internacao)
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'CARATER_INTERNACAO'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `CARATER_INTERNACAO` varchar(2) NULL AFTER `DT_SAIDA`',
  'SELECT ''s_aih.CARATER_INTERNACAO já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- DIAG_SECUNDARIO (ah_diag_sec)
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'DIAG_SECUNDARIO'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `DIAG_SECUNDARIO` varchar(4) NULL AFTER `DIAG_PRINCIPAL`',
  'SELECT ''s_aih.DIAG_SECUNDARIO já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- CID_OBITO (ah_diag_obito / ah_cid_obito)
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 's_aih' AND COLUMN_NAME = 'CID_OBITO'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `s_aih` ADD COLUMN `CID_OBITO` varchar(4) NULL AFTER `MOTIVO_SAIDA`',
  'SELECT ''s_aih.CID_OBITO já existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2) Chave única uk_aih → (AIH, CNES, COMPETENCIA, DT_SAIDA)
--    Permite a mesma AIH mais de uma vez na competência (reabertura UTI).
-- -----------------------------------------------------------------------------

SET @uk_has_dt_saida := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 's_aih'
    AND INDEX_NAME = 'uk_aih'
    AND COLUMN_NAME = 'DT_SAIDA'
);

SET @uk_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 's_aih'
    AND INDEX_NAME = 'uk_aih'
);

-- Se uk_aih existe sem DT_SAIDA: drop + recreate
SET @sql := IF(@uk_exists > 0 AND @uk_has_dt_saida = 0,
  'ALTER TABLE `s_aih` DROP INDEX `uk_aih`, ADD UNIQUE KEY `uk_aih` (`AIH`,`CNES`,`COMPETENCIA`,`DT_SAIDA`)',
  IF(@uk_exists = 0,
    'ALTER TABLE `s_aih` ADD UNIQUE KEY `uk_aih` (`AIH`,`CNES`,`COMPETENCIA`,`DT_SAIDA`)',
    'SELECT ''uk_aih já inclui DT_SAIDA'' AS info'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 3) Registrar migrations no Laravel (opcional — só se a tabela existir)
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

SET @sql := IF(@migrations_exists > 0 AND (
  SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_07_10_000000_update_s_aih_unique_key'
) = 0,
  CONCAT('INSERT INTO `migrations` (`migration`, `batch`) VALUES (''2026_07_10_000000_update_s_aih_unique_key'', ', @batch, ')'),
  'SELECT ''migration uk_aih DT_SAIDA já registrada ou tabela migrations ausente'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@migrations_exists > 0 AND (
  SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_07_10_203500_add_extended_fields_to_s_aih'
) = 0,
  CONCAT('INSERT INTO `migrations` (`migration`, `batch`) VALUES (''2026_07_10_203500_add_extended_fields_to_s_aih'', ', @batch, ')'),
  'SELECT ''migration campos estendidos s_aih já registrada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 4) Verificação final
-- -----------------------------------------------------------------------------

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 's_aih'
  AND COLUMN_NAME IN (
    'IDENT_AIH', 'MUN_RESIDENCIA', 'CARATER_INTERNACAO',
    'DIAG_SECUNDARIO', 'CID_OBITO', 'DT_SAIDA'
  )
ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 's_aih'
  AND INDEX_NAME = 'uk_aih'
ORDER BY SEQ_IN_INDEX;

SELECT 'Atualização AIH 2026-07 concluída com sucesso.' AS status;

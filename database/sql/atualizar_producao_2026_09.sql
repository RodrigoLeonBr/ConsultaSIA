-- ============================================================================
-- Atualização producao — Vigência da tabela CISMETRO
-- Adiciona competencia_inicial / competencia_final à tabela `cismetro`
-- (mesma semântica de sus_paulista). Aplicar via phpMyAdmin ou mysql CLI.
-- Aditivo — não recria colunas existentes.
-- ============================================================================

ALTER TABLE `cismetro`
  ADD COLUMN `competencia_inicial` CHAR(6) NOT NULL DEFAULT '202301' AFTER `tipo_valor`,
  ADD COLUMN `competencia_final` CHAR(6) NOT NULL DEFAULT '999999' AFTER `competencia_inicial`,
  ADD KEY `cismetro_vigencia_index` (`competencia_inicial`, `competencia_final`);

-- Backfill: dados atuais são "Credencimento 2023".
UPDATE `cismetro`
   SET `competencia_inicial` = '202301',
       `competencia_final`   = '999999';

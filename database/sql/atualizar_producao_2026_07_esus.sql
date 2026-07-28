-- ============================================================================
-- Atualização producao — Importação e-SUS (produção SIGTAP via API)
-- Cria a tabela auxiliar s_esus (produção) e adiciona a flag prestador.esus_ativo.
-- Aplicar via phpMyAdmin ou mysql CLI. Não recria colunas DATASUS do prestador.
-- (De-para esus_unidade foi descontinuado — CNES vem no payload da API.)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `s_esus` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `competencia` varchar(7) NOT NULL,
  `cnes` varchar(7) DEFAULT NULL,
  `unidade` varchar(180) NOT NULL,
  `tipo_relatorio` varchar(60) DEFAULT NULL,
  `bloco` varchar(120) DEFAULT NULL,
  `descricao_esus` varchar(180) DEFAULT NULL,
  `codigo_sigtap` varchar(10) NOT NULL,
  `descricao_sigtap` varchar(180) DEFAULT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_esus` (`competencia`,`unidade`,`tipo_relatorio`,`bloco`,`codigo_sigtap`,`descricao_esus`),
  KEY `idx_esus_cmp` (`competencia`),
  KEY `idx_esus_cnes` (`cnes`),
  KEY `idx_esus_cnes_cmp` (`cnes`,`competencia`),
  KEY `idx_esus_sigtap` (`codigo_sigtap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Flag: unidade considerada nas consultas/relatórios e-SUS (padrão sim=1).
ALTER TABLE `prestador`
  ADD COLUMN `esus_ativo` tinyint(1) NOT NULL DEFAULT 1 AFTER `ativo`,
  ADD KEY `prestador_esus_ativo_index` (`esus_ativo`);

-- Descontinuação do de-para (rodar só se a tabela existir):
DROP TABLE IF EXISTS `esus_unidade`;

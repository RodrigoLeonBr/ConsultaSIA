-- ============================================================
-- ConsultaProd — Tabelas SIH (Internações AIH)
-- Gerado em: 2026-06-21
-- Executar no banco: producao
-- ============================================================

-- Remover tabelas se já existirem (ordem: dependente primeiro)
DROP TABLE IF EXISTS `s_aih_pa`;
DROP TABLE IF EXISTS `s_aih`;

-- ============================================================
-- Tabela: s_aih
-- Resumo das internações (TB_HAIH do SIHD)
-- Formato de importação: arquivo .txt separado por ";"
-- 23 colunas, sem linha de cabeçalho, decimal BR (vírgula)
-- ============================================================

CREATE TABLE `s_aih` (
  `id`              bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Identificação
  `AIH`             varchar(13)  NOT NULL COMMENT 'Número da AIH (ah_num_aih)',
  `IDENT_AIH`       varchar(2)   DEFAULT NULL COMMENT 'Identificador da AIH (ah_ident)',
  `CNES`            varchar(7)   NOT NULL COMMENT 'CNES da unidade (ah_cnes)',
  `COMPETENCIA`     varchar(6)   NOT NULL COMMENT 'Competência AAAAMM (ah_cmpt)',
  `MUN_RESIDENCIA`  varchar(6)   DEFAULT NULL COMMENT 'Município de residência (ah_mun_paci)',

  -- Dados do paciente
  `DT_NASC`         varchar(8)   DEFAULT NULL COMMENT 'Data de nascimento AAAAMMDD',
  `IDADE`           int(3)       DEFAULT NULL COMMENT 'Idade em anos (ah_paciente_idade)',
  `SEXO_PACIENTE`   varchar(1)   DEFAULT NULL COMMENT 'M ou F',

  -- Datas da internação
  `DT_INT`          varchar(8)   DEFAULT NULL COMMENT 'Data de internação AAAAMMDD',
  `DT_SAIDA`        varchar(8)   DEFAULT NULL COMMENT 'Data de saída AAAAMMDD',
  `CARATER_INTERNACAO` varchar(2) DEFAULT NULL COMMENT 'Caráter do atendimento (ah_car_internacao)',

  -- Classificação clínica
  `ESPECIALIDADE`   varchar(3)   DEFAULT NULL,
  `PROC_PRINCIPAL`  varchar(10)  DEFAULT NULL COMMENT 'Procedimento realizado (ah_proc_realizado)',
  `DIAG_PRINCIPAL`  varchar(4)   DEFAULT NULL COMMENT 'CID-10 principal (ah_diag_pri)',
  `DIAG_SECUNDARIO` varchar(4)   DEFAULT NULL COMMENT 'CID-10 secundário (ah_diag_sec)',
  `COMPLEXIDADE`    varchar(2)   DEFAULT NULL,
  `FINANCIAMENTO`   varchar(2)   DEFAULT NULL,
  `ENFERMARIA`      varchar(4)   DEFAULT NULL,
  `MOTIVO_SAIDA`    varchar(2)   DEFAULT NULL,
  `CID_OBITO`       varchar(4)   DEFAULT NULL COMMENT 'CID do óbito (ah_cid_obito)',

  -- Quantitativos
  `DIARIAS`         int(5)       DEFAULT NULL COMMENT 'Total de diárias',
  `DIARIAS_UTI`     int(5)       DEFAULT NULL COMMENT 'Diárias em UTI',

  -- Valor
  `VALOR_TOTAL_AIH` decimal(12,2) DEFAULT NULL COMMENT 'Valor total (SUM TB_HPA.pa_valor)',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_aih` (`AIH`, `CNES`, `COMPETENCIA`, `DT_SAIDA`),
  KEY `idx_aih_cnes`     (`CNES`),
  KEY `idx_aih_cmp`      (`COMPETENCIA`),
  KEY `idx_aih_cnes_cmp` (`CNES`, `COMPETENCIA`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Tabela: s_aih_pa
-- Procedimentos detalhados por internação (TB_HPA do SIHD)
-- Formato de importação: arquivo .txt separado por ";"
-- 8 colunas, sem linha de cabeçalho, decimal BR (vírgula)
-- ============================================================

CREATE TABLE `s_aih_pa` (
  `id`                   bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Vínculo com s_aih
  `AIH`                  varchar(13)   NOT NULL COMMENT 'Número da AIH (pa_num_aih)',
  `CNES`                 varchar(7)    NOT NULL COMMENT 'CNES da unidade (pa_cnes)',
  `COMPETENCIA`          varchar(6)    NOT NULL COMMENT 'Competência AAAAMM (pa_cmpt)',

  -- Procedimento
  `PROC_DETALHADO`       varchar(10)   DEFAULT NULL COMMENT 'Código do procedimento (pa_procedimento)',
  `QUANTIDADE`           int(6)        DEFAULT NULL COMMENT 'Quantidade produzida (pa_procedimento_qtd)',
  `VALOR_ITEM`           decimal(12,2) DEFAULT NULL COMMENT 'Valor do item (pa_valor)',
  `FINANCIAMENTO_DETALHE` varchar(2)   DEFAULT NULL COMMENT 'Financiamento (pa_financiamento)',
  `CBO_PROFISSIONAL`     varchar(6)    DEFAULT NULL COMMENT 'CBO do profissional (pa_pf_cbo)',

  PRIMARY KEY (`id`),
  KEY `idx_aih_pa_aih`     (`AIH`),
  KEY `idx_aih_pa_cnes`    (`CNES`),
  KEY `idx_aih_pa_cmp`     (`COMPETENCIA`),
  KEY `idx_aih_pa_cnes_cmp` (`CNES`, `COMPETENCIA`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Verificação (opcional — executar após criação)
-- ============================================================
-- SHOW CREATE TABLE `s_aih`;
-- SHOW CREATE TABLE `s_aih_pa`;
-- SELECT COUNT(*) FROM `s_aih`;
-- SELECT COUNT(*) FROM `s_aih_pa`;

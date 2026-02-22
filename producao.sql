-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/01/2026 às 12:25
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `producao`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cbo`
--

CREATE TABLE `cbo` (
  `cbo` varchar(6) NOT NULL,
  `ds_cbo` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cbo`
--

INSERT INTO `cbo` (`cbo`, `ds_cbo`) VALUES
('225125', 'MÉDICO CLÍNICO'),
('225142', 'MÉDICO GINECOLOGISTA E OBSTETRA'),
('225170', 'MÉDICO PEDIATRA');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cismetro`
--

CREATE TABLE `cismetro` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(11) NOT NULL,
  `credenciamento` varchar(40) NOT NULL,
  `grupo` varchar(40) NOT NULL,
  `descricao` varchar(180) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `forma`
--

CREATE TABLE `forma` (
  `id_registro` int(11) NOT NULL,
  `grupo` varchar(2) NOT NULL,
  `subgrupo` varchar(4) NOT NULL,
  `forma` varchar(6) NOT NULL,
  `descricao` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_09_17_182713_create_cbo_table', 1),
(5, '2025_09_17_182716_create_prestador_table', 1),
(6, '2025_09_17_182717_create_procedimento_table', 1),
(7, '2025_09_17_182718_create_s_rub_table', 1),
(8, '2025_09_17_182719_create_s_prd_table', 1),
(9, '2025_09_17_184500_add_must_change_password_to_users_table', 1),
(12, '2025_10_16_191832_create_forma_table', 1),
(13, '2025_10_22_125451_create_cismetro_table', 1),
(14, '2025_10_23_130415_update_prestador_relatorio_field_size', 1),
(15, '2025_12_17_152908_add_matrix_performance_indexes', 1),
(17, '2025_09_19_160001_create_s_pap_table', 3),
(19, '2025_09_19_160000_create_s_apa_table', 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prestador`
--

CREATE TABLE `prestador` (
  `re_cunid` varchar(7) NOT NULL,
  `re_cnome` varchar(35) NOT NULL,
  `re_tipo` char(1) NOT NULL,
  `cnpj` varchar(14) DEFAULT NULL,
  `area` int(11) NOT NULL,
  `tipouni` char(1) NOT NULL,
  `relatorio` varchar(40) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `prestador`
--

INSERT INTO `prestador` (`re_cunid`, `re_cnome`, `re_tipo`, `cnpj`, `area`, `tipouni`, `relatorio`, `ativo`) VALUES
('2048205', 'NUCLEO DE ESPECIALIDADES', 'P', NULL, 4, 'M', 'Aten??o Ambu', 1),
('2080486', 'HOSP INFANTIL ANDRE LUIZ', 'P', '44.682.821/000', 4, 'M', 'Hospitalar', 1),
('2082179', 'HOSPITAL SAO FRANCISCO', 'P', '43.252.758/000', 1, 'F', 'Hospitalar', 1),
('2047020', 'P.M. 01 VILA MATHIENSEN', 'U', NULL, 10, 'M', 'Aten??o B?si', 1),
('2047039', 'P.M. 02 PRAIA AZUL', 'U', NULL, 3, 'M', 'Aten??o B?si', 1),
('2047047', 'P.M. 03 SAO VITO', 'U', NULL, 4, 'M', 'Aten??o B?si', 1),
('2067471', 'P.M. 04 JD. GUANABARA', 'U', NULL, 4, 'M', 'Aten??o B?si', 1),
('2063425', 'P.M. 05 VILA DAINESE', 'U', NULL, 7, 'M', 'Aten??o B?si', 1),
('2028042', 'P.M. 06 JD. IPIRANGA', 'U', NULL, 8, 'M', 'Aten??o B?si', 1),
('2028050', 'PRONTO SOCORRO ZANAGA', 'P', NULL, 2, 'M', 'Aten??o B?si', 1),
('2028034', 'P.M. 10 ZANAGA II', 'U', NULL, 2, 'M', 'Aten??o B?si', 1),
('2040530', 'P.M. 08 JD ALVORADA', 'U', NULL, 10, 'M', 'Aten??o B?si', 1),
('2042592', 'P.M. 09 CARIOBINHA', 'U', NULL, 4, 'M', 'Aten??o B?si', 1),
('2058790', 'FUSAME HOSP MUNICIPAL', 'M', '47.716.204/000', 5, 'M', 'Hospitalar', 1),
('2075040', 'P.M. 11 - PQUE GRAMADO', 'U', NULL, 6, 'M', 'Aten??o B?si', 1),
('2066289', 'P.M. 12 SAO LUIZ', 'U', NULL, 5, 'M', 'Aten??o B?si', 1),
('2074923', 'P.M. 13 ANTONIO ZANAGA II', 'U', NULL, 2, 'M', 'Aten??o B?si', 1),
('2033771', 'RAD SIDNEY S. ALMEIDA', 'P', '51.413.185/000', 1, 'P', 'Aten??o Ambu', 1),
('2042584', 'CLINICA JONES', 'P', NULL, 1, 'P', 'Aten??o Ambu', 1),
('2034271', 'CLIN MED. ODONTO ZANAGA', 'U', NULL, 2, 'M', 'Aten??o B?si', 1),
('2040840', 'UNIDADE AMBULATORIAL D SAUDE MENTAL', 'P', NULL, 1, 'M', 'Aten??o Psic', 1),
('2073420', 'P.M. 14 JARDIM BRASIL', 'U', NULL, 2, 'M', 'Aten??o B?si', 1),
('2047985', 'UNIDADE DE VIGILANCIA EM SAUDE', 'P', NULL, 5, 'M', 'Aten??o Ambu', 1),
('2066807', 'P.M. 15 SAO DOMINGOS', 'U', NULL, 7, 'M', 'Aten??o B?si', 1),
('2027992', 'P.M. 16 VILA GALO', 'U', NULL, 9, 'M', 'Aten??o B?si', 1),
('2688263', 'CORPO DE BOMBEIROS', 'P', NULL, 5, 'E', 'Aten??o Ambu', 1),
('2030748', 'UNID ATEND DOMICILIAR', 'P', NULL, 9, 'M', 'Aten??o Ambu', 1),
('2047993', 'P.M. 22 JD AMERICA II', 'U', NULL, 3, 'M', 'Aten??o B?si', 1),
('2047977', 'POSTO MEDICO DO CAIC', 'U', '', 6, 'M', 'Atenção Bási', 0),
('2047969', 'P.M. 19 PQ. DA LIBERDADE', 'U', NULL, 6, 'M', 'Aten??o B?si', 1),
('2028026', 'APAE  - AMERICANA', 'P', NULL, 8, 'F', 'Aten??o Ambu', 1),
('2073331', 'POSTO MEDICO 20 - CENTRAL', 'U', NULL, 1, 'M', 'Aten??o B?si', 1),
('2066297', 'CENTRO FISIOT RE ESTETICA SC LTDA', 'P', NULL, 5, 'P', 'Aten??o Ambu', 1),
('2067633', 'UNIFISIO FISIOTERAPIA E REAB S/C LT', 'P', '', 1, 'P', 'Atenção Ambu', 0),
('2059045', 'LITOCLINICA', 'P', '02.267.972/000', 1, 'P', 'Aten??o Ambu', 1),
('0009326', 'UNICAMP', 'P', '46.068.425/000', 0, 'M', 'Aten??o Ambu', 1),
('2716607', 'CENTRO DE ATENCAO PSICO SOCIAL', 'P', NULL, 1, 'M', 'Aten??o Psic', 1),
('2688239', 'FASP', 'P', NULL, 2, 'P', 'Aten??o Ambu', 1),
('2688247', 'P.M. 21 PARQUE DAS NACOES', 'U', NULL, 6, 'M', 'Aten??o B?si', 1),
('3939782', 'INSTITUTO DE OLHOS AMERICANA S/C LT', 'P', NULL, 8, 'P', 'Aten??o Ambu', 1),
('5129915', 'CENTRO ATEN??O PSICOSOCIAL INFANTIL', 'P', '45781176000166', 9, 'M', 'Aten??o Psic', 1),
('6063470', 'CENTRO ODONTOLOGICO INTEGRADO', 'U', '45781176000166', 2, 'M', 'Aten??o B?si', 1),
('2067641', 'CETAM', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('6449786', 'ESF 24 MARIO COVAS', 'U', NULL, 0, 'M', 'Aten??o B?si', 1),
('6458203', 'PM 17 SAO JOSE', 'U', NULL, 0, 'M', 'Aten??o B?si', 1),
('6458130', 'ESF JAGUARI', 'U', NULL, 0, 'M', 'Aten??o B?si', 1),
('6673554', 'UBS Cillos / CLIN FONO', 'U', NULL, 0, 'M', 'Aten??o B?si', 1),
('6695965', 'CLINICA VIVERE', 'P', '11055873000118', 0, 'P', 'Aten??o Ambu', 1),
('6928544', 'SAE - DST/HIV/AIDS', 'P', NULL, 0, 'M', 'Aten??o Ambu', 1),
('3852989', 'PRO COR DO CORACAO LTDA', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('7169698', 'CAFI - CENTRO DE ASSIST?NCIA A FAMI', 'U', NULL, 0, 'M', 'Aten??o B?si', 1),
('7218893', 'CENTRO AT ? SA?DE DO HOMEM E MULHER', 'U', NULL, 0, 'M', 'Aten??o B?si', 1),
('7261004', 'INTERCARE CLINICA CARDIOLOGICA', 'P', '07313106000194', 0, 'P', 'Aten??o Ambu', 1),
('7446861', 'CAPS AD - NOVA VIDA', 'P', NULL, 0, '', 'Aten??o Psic', 1),
('7417772', 'ATRIUM', 'P', '00.777.199/000', 0, 'P', 'Aten??o Ambu', 1),
('9580565', 'CONSULTEMED', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9579885', 'CLINICA ADAMSON', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('3769054', 'WAGNER FRANCOZO', 'P', '', 0, 'P', 'Atenção Ambu', 0),
('3599647', 'ROSANGELA GALLARDO DE MORAES', 'P', '', 0, 'P', 'Atenção Ambu', 0),
('3597938', 'GUSTAVO LEME FRANCO DE ANDRADE', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('6101437', 'OTOFONO', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9194983', 'CLIMED', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('3598128', 'CINCOR', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9663134', 'CLINICA VITAL VIDA SAUDE E BEM ESTA', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('0148806', 'CLINICA DE ANGIOLOGIA DIACOV', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9050965', 'CORREA & BERGAMO', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('2028077', 'DIGIMAX UNIDADE RADIOL?GICA', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9971947', 'DUE VITAE', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('5881846', 'DA VINCI CL?NICA M?DICA/DR HERMINIO', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9752749', 'CLINIPLAST - 20/20', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('9993452', 'ENDOSKOPICA', 'P', '25.107.055/000', 0, 'P', 'Aten??o Ambu', 1),
('3120368', 'ASSISTENCIA E SA?DE', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('0751073', 'B&B SERVI?OS M?DICOS', 'P', '', 0, 'P', 'Atenção Ambu', 1),
('3687554', 'CLINICA M?DICA C?SIMO', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('4006356', 'HDO', 'P', NULL, 0, 'P', 'Aten??o Ambu', 1),
('7471777', 'UPA AVENIDA CILLOS', 'P', '', 0, 'P', 'Aten??o B?si', 1),
('4032128', 'UBS DONA ROSA', 'U', '', 6, 'M', 'Aten??o B?si', 1),
('7406207', 'CDP AMERICANA', 'U', '', 0, 'M', 'Aten??o B?si', 1),
('4777220', 'UPA DONA ROSA', 'P', '', 0, 'P', 'Aten??o B?si', 1),
('4781104', 'DIMAZE', 'P', '000000000', 0, 'P', 'Aten??o Ambu', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `s_apa`
--

CREATE TABLE `s_apa` (
  `APA_UID` varchar(7) DEFAULT NULL,
  `APA_NUM` varchar(13) DEFAULT NULL,
  `APA_EMISSA` varchar(8) DEFAULT NULL,
  `APA_DTINIC` varchar(8) DEFAULT NULL,
  `APA_DTFIM` varchar(8) DEFAULT NULL,
  `APA_TPATEN` varchar(2) DEFAULT NULL,
  `APA_TPAPAC` varchar(1) DEFAULT NULL,
  `APA_NMPCN` varchar(30) DEFAULT NULL,
  `APA_UFPCN` varchar(3) DEFAULT NULL,
  `APA_MAEPCN` varchar(30) DEFAULT NULL,
  `APA_LOGPCN` varchar(30) DEFAULT NULL,
  `APA_NUMPCN` varchar(5) DEFAULT NULL,
  `APA_CPLPCN` varchar(10) DEFAULT NULL,
  `APA_CEPPCN` varchar(8) DEFAULT NULL,
  `APA_MUNPCN` varchar(7) DEFAULT NULL,
  `APA_DTNASC` varchar(8) DEFAULT NULL,
  `APA_SEXPCN` varchar(1) DEFAULT NULL,
  `APA_VARIA` varchar(141) DEFAULT NULL,
  `APA_CPFRES` varchar(11) DEFAULT NULL,
  `APA_NMRES` varchar(30) DEFAULT NULL,
  `APA_MOTCOB` varchar(2) DEFAULT NULL,
  `APA_DTOBAL` varchar(8) DEFAULT NULL,
  `APA_CPFDIR` varchar(11) DEFAULT NULL,
  `APA_NMDIR` varchar(30) DEFAULT NULL,
  `APA_CMP` varchar(6) DEFAULT NULL,
  `APA_MVM` varchar(6) DEFAULT NULL,
  `APA_RMS` varchar(4) DEFAULT NULL,
  `APA_DTGER` varchar(8) DEFAULT NULL,
  `APA_FLER` varchar(10) DEFAULT NULL,
  `APA_INERPP` varchar(1) DEFAULT NULL,
  `APA_PRIPAL` varchar(9) DEFAULT NULL,
  `APA_CPFPCT` varchar(11) DEFAULT NULL,
  `APA_CNSPCT` varchar(15) DEFAULT NULL,
  `APA_CNSRES` varchar(15) DEFAULT NULL,
  `APA_CNSDIR` varchar(15) DEFAULT NULL,
  `APA_CIDCA` varchar(4) DEFAULT NULL,
  `APA_NPRONT` varchar(10) DEFAULT NULL,
  `APA_CODSOL` varchar(7) DEFAULT NULL,
  `APA_DTSOL` varchar(8) DEFAULT NULL,
  `APA_DTAUT` varchar(8) DEFAULT NULL,
  `APA_CODEMI` varchar(10) DEFAULT NULL,
  `APA_CATEND` varchar(2) DEFAULT NULL,
  `APA_APACAN` varchar(14) DEFAULT NULL,
  `APA_RACA` varchar(2) DEFAULT NULL,
  `APA_NOMERE` varchar(30) DEFAULT NULL,
  `APA_ETNIA` varchar(4) DEFAULT NULL,
  `APA_ADVLMC` varchar(1) DEFAULT NULL,
  `APA_ADVTZM` varchar(1) DEFAULT NULL,
  `APA_SRV` varchar(3) DEFAULT NULL,
  `APA_CSF` varchar(3) DEFAULT NULL,
  `APA_CDLOGR` varchar(3) DEFAULT NULL,
  `APA_BAIRRO` varchar(30) DEFAULT NULL,
  `APA_DDD` varchar(2) DEFAULT NULL,
  `APA_TEL` varchar(9) DEFAULT NULL,
  `APA_EMAIL` varchar(40) DEFAULT NULL,
  `APA_CNSEXE` varchar(15) DEFAULT NULL,
  `APA_INE` varchar(10) DEFAULT NULL,
  `APA_ADVSEX` varchar(1) DEFAULT NULL,
  `APA_EXPMAE` varchar(1) DEFAULT NULL,
  `APA_STRUA` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `s_pap`
--

CREATE TABLE `s_pap` (
  `PAP_UID` varchar(7) DEFAULT NULL,
  `PAP_CMP` varchar(6) DEFAULT NULL,
  `PAP_NUM` varchar(13) DEFAULT NULL,
  `PAP_PA` varchar(10) DEFAULT NULL,
  `PAP_SEQ` varchar(2) DEFAULT NULL,
  `PAP_CBO` varchar(6) DEFAULT NULL,
  `PAP_IDADE` smallint(6) DEFAULT NULL,
  `PAP_QT_P` double DEFAULT NULL,
  `PAP_QT_A` double DEFAULT NULL,
  `PAP_MVM` varchar(6) DEFAULT NULL,
  `PAP_ORG` varchar(3) DEFAULT NULL,
  `PAP_FLPA` varchar(1) DEFAULT NULL,
  `PAP_FLEMA` varchar(1) DEFAULT NULL,
  `PAP_FLCBO` varchar(1) DEFAULT NULL,
  `PAP_FLQT` varchar(1) DEFAULT NULL,
  `PAP_FLER` varchar(1) DEFAULT NULL,
  `PAP_CNPJ` varchar(14) DEFAULT NULL,
  `PAP_NFISC` varchar(6) DEFAULT NULL,
  `PAP_CIDPRI` varchar(6) DEFAULT NULL,
  `PAP_CIDSEC` varchar(6) DEFAULT NULL,
  `PAP_EQUIPE` varchar(12) DEFAULT NULL,
  `PAP_VL_FED` double DEFAULT NULL,
  `PAP_VL_LOC` double DEFAULT NULL,
  `PAP_VL_INC` double DEFAULT NULL,
  `PAP_INCOUT` varchar(4) DEFAULT NULL,
  `PAP_INCURG` varchar(4) DEFAULT NULL,
  `PAP_RUB` varchar(6) DEFAULT NULL,
  `PAP_TPFIN` varchar(1) DEFAULT NULL,
  `PAP_CPX` varchar(1) DEFAULT NULL,
  `PAP_RC` varchar(4) DEFAULT NULL,
  `PAP_UNTERC` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `s_rub`
--

CREATE TABLE `s_rub` (
  `rub_id` char(4) NOT NULL,
  `rub_dc` char(40) NOT NULL DEFAULT '',
  `rub_total` char(2) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `s_rub`
--

INSERT INTO `s_rub` (`rub_id`, `rub_dc`, `rub_total`) VALUES
('01', 'TESOURO NACIONAL', ''),
('02', 'RECURSOS PRÓPRIOS', ''),
('03', 'CONVÊNIOS', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'operator',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `active`, `must_change_password`, `password_changed_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$RnFF6RB5JhbqzhxDqz7ki.0Cz47kcgmsCNt6dATL2ZcS2//bVTTV.', 'admin@sistema.com', 'Administrador', 'Sistema', 'admin', 1, 0, '2025-12-24 04:10:24', '2025-12-24 04:10:25', '2025-12-24 04:10:25');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Índices de tabela `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Índices de tabela `cbo`
--
ALTER TABLE `cbo`
  ADD PRIMARY KEY (`cbo`);

--
-- Índices de tabela `cismetro`
--
ALTER TABLE `cismetro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cismetro_codigo_index` (`codigo`),
  ADD KEY `cismetro_credenciamento_index` (`credenciamento`),
  ADD KEY `cismetro_grupo_index` (`grupo`);

--
-- Índices de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Índices de tabela `forma`
--
ALTER TABLE `forma`
  ADD PRIMARY KEY (`id_registro`),
  ADD KEY `forma_grupo_index` (`grupo`),
  ADD KEY `forma_subgrupo_index` (`subgrupo`),
  ADD KEY `forma_forma_index` (`forma`);

--
-- Índices de tabela `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Índices de tabela `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Índices de tabela `prestador`
--
ALTER TABLE `prestador`
  ADD PRIMARY KEY (`re_cunid`),
  ADD KEY `idx_cnpj` (`cnpj`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Índices de tabela `s_apa`
--
ALTER TABLE `s_apa`
  ADD KEY `s_apa_apa_num_index` (`APA_NUM`),
  ADD KEY `s_apa_apa_uid_index` (`APA_UID`),
  ADD KEY `s_apa_apa_pripal_index` (`APA_PRIPAL`),
  ADD KEY `s_apa_apa_mvm_index` (`APA_MVM`);

--
-- Índices de tabela `s_pap`
--
ALTER TABLE `s_pap`
  ADD KEY `idx_pap_composite` (`PAP_UID`,`PAP_CMP`,`PAP_NUM`),
  ADD KEY `s_pap_pap_num_index` (`PAP_NUM`),
  ADD KEY `s_pap_pap_uid_index` (`PAP_UID`),
  ADD KEY `s_pap_pap_pa_index` (`PAP_PA`),
  ADD KEY `s_pap_pap_cbo_index` (`PAP_CBO`),
  ADD KEY `s_pap_pap_mvm_index` (`PAP_MVM`);

--
-- Índices de tabela `s_rub`
--
ALTER TABLE `s_rub`
  ADD PRIMARY KEY (`rub_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_username_index` (`username`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_active_index` (`active`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cismetro`
--
ALTER TABLE `cismetro`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

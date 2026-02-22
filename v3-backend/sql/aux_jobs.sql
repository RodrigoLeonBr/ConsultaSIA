-- v3-backend/sql/aux_jobs.sql
-- DDL para as tabelas auxiliares de motor de relatórios em ambiente XAMPP (MariaDB/MySQL).
-- Este schema permite execuções demoradas ("Jobs") persistidas no banco e listagens paginadas.

CREATE TABLE IF NOT EXISTS producao.report_job (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL COMMENT 'Identificador/Tipo do relatório',
    payload_json JSON NULL COMMENT 'Filtros e argumentos originais da requisição',
    status ENUM('queued', 'running', 'done', 'failed') NOT NULL DEFAULT 'queued',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 a 100 indicando % do progresso',
    requested_by BIGINT UNSIGNED NULL COMMENT 'ID do usuário (users.id) que requisitou',
    error_message TEXT NULL COMMENT 'Stack trace ou mensagem descritiva de falha',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    INDEX idx_job_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producao.report_result_header (
    result_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    competence VARCHAR(10) NULL COMMENT 'Mês/Ano ou Período caso aplicável',
    filters_hash VARCHAR(64) NULL COMMENT 'Hash dos filtros para reaproveitar relatórios idênticos',
    source_tables_versions_json JSON NULL COMMENT 'Metadados de momento/data snapshot',
    row_count INT NOT NULL DEFAULT 0 COMMENT 'Total final de linhas processadas',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ttl_expires_at TIMESTAMP NULL COMMENT 'Momento em que o lixo (cleanup) poderá remover o resultado',
    CONSTRAINT fk_report_result_header_job FOREIGN KEY (job_id) REFERENCES producao.report_job(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producao.report_result_rows (
    result_id BIGINT NOT NULL,
    row_index INT NOT NULL COMMENT 'Índice de 1 a N para determinar a ordem original gerada e facilitar o OFFSET',
    row_json JSON NOT NULL COMMENT 'Colunas processadas completas da linha de relatório',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (result_id, row_index),
    CONSTRAINT fk_report_result_rows_header FOREIGN KEY (result_id) REFERENCES producao.report_result_header(result_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

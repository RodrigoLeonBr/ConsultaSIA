CREATE TABLE IF NOT EXISTS report_job (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  status ENUM('queued','running','done','failed') NOT NULL DEFAULT 'queued',
  type VARCHAR(80) NOT NULL,
  parameters JSON NOT NULL,
  error_message VARCHAR(2000),
  created_at DATETIME NOT NULL,
  started_at DATETIME,
  completed_at DATETIME,
  INDEX idx_status_created (status, created_at)
);

CREATE TABLE IF NOT EXISTS report_result_header (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  report_type VARCHAR(80),
  competence VARCHAR(6),
  total_rows_fetched INT NOT NULL DEFAULT 0,
  columns_json JSON,
  source_tables_versions_json JSON,
  ttl DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_job_id (job_id)
);

CREATE TABLE IF NOT EXISTS report_result_rows (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  header_id INT NOT NULL,
  row_index INT NOT NULL,
  row_json LONGTEXT NOT NULL,
  INDEX idx_header_idx (header_id, row_index)
);

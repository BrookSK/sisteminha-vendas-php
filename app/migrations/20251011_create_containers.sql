-- Migration: create containers table and optional FK
-- Run this in your MySQL/MariaDB database

START TRANSACTION;

CREATE TABLE IF NOT EXISTS containers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilizador_id INT NULL,
  invoice_id VARCHAR(100) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'Em preparo',
  created_at DATE NOT NULL,
  peso_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  peso_lbs DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  transporte_aeroporto_correios_brl DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  transporte_caminhao_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  transporte_mercadoria_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  aereo_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valor_debitado_final_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valor_debitado_final_brl DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  vendas_ids TEXT NULL,
  INDEX idx_created_at (created_at),
  INDEX idx_status (status),
  INDEX idx_utilizador (utilizador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: add FK to usuarios(id). Comment out if usuarios table name differs.
-- ALTER TABLE containers
--   ADD CONSTRAINT fk_containers_usuarios
--   FOREIGN KEY (utilizador_id) REFERENCES usuarios(id)
--   ON DELETE SET NULL ON UPDATE CASCADE;

-- Optional: seed default settings used by containers (only if not exists)
INSERT INTO settings(`key`, `value`)
SELECT 'lbs_per_kg', '2.2'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'lbs_per_kg');

COMMIT;

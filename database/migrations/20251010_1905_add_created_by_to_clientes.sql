-- Migration: add created_by ownership to clientes
-- Run this against your MySQL database (use the same schema configured in app/config/config.php)

START TRANSACTION;

-- 1) Add column
ALTER TABLE clientes
  ADD COLUMN created_by INT NULL AFTER observacoes;

-- 2) Index for faster filtering by owner
CREATE INDEX idx_clientes_created_by ON clientes (created_by);

-- 3) Foreign key to usuarios(id). If user is deleted, keep client but nullify owner
ALTER TABLE clientes
  ADD CONSTRAINT fk_clientes_created_by
  FOREIGN KEY (created_by) REFERENCES usuarios(id)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

COMMIT;

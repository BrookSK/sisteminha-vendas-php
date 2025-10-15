-- Costs recurrence fields on custos table
ALTER TABLE custos
  ADD COLUMN IF NOT EXISTS recorrente_tipo ENUM('none','weekly','monthly','yearly','installments') NOT NULL DEFAULT 'none',
  ADD COLUMN IF NOT EXISTS recorrente_ativo TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS recorrente_proxima_data DATE NULL,
  ADD COLUMN IF NOT EXISTS parcelas_total INT NULL,
  ADD COLUMN IF NOT EXISTS parcela_atual INT NULL,
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL;

-- Seed defaults for existing rows
UPDATE custos SET recorrente_tipo = 'none', recorrente_ativo = 0 WHERE recorrente_tipo IS NULL;

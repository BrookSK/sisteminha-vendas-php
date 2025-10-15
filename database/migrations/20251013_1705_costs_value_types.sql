-- Add value types to custos
ALTER TABLE custos
  ADD COLUMN IF NOT EXISTS valor_tipo ENUM('usd','brl','percent') NOT NULL DEFAULT 'usd',
  ADD COLUMN IF NOT EXISTS valor_brl DECIMAL(12,2) NULL,
  ADD COLUMN IF NOT EXISTS valor_percent DECIMAL(5,2) NULL;

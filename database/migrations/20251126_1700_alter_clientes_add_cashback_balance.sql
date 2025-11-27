ALTER TABLE clientes
  ADD COLUMN cashback_balance_usd DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER observacoes;

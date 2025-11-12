-- Add snapshot and cost fields to comissoes to fully freeze past periods
ALTER TABLE comissoes
  ADD COLUMN IF NOT EXISTS vendedor_name VARCHAR(255) NULL AFTER vendedor_id,
  ADD COLUMN IF NOT EXISTS vendedor_email VARCHAR(255) NULL AFTER vendedor_name,
  ADD COLUMN IF NOT EXISTS vendedor_role VARCHAR(20) NULL AFTER vendedor_email,
  ADD COLUMN IF NOT EXISTS vendedor_ativo TINYINT(1) NULL AFTER vendedor_role,
  ADD COLUMN IF NOT EXISTS allocated_cost DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER liquido_total,
  ADD COLUMN IF NOT EXISTS liquido_apurado DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER allocated_cost,
  ADD COLUMN IF NOT EXISTS percent_individual DECIMAL(10,6) NULL AFTER liquido_apurado,
  ADD COLUMN IF NOT EXISTS bruto_total_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER percent_individual,
  ADD COLUMN IF NOT EXISTS liquido_total_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER bruto_total_brl,
  ADD COLUMN IF NOT EXISTS allocated_cost_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER liquido_total_brl,
  ADD COLUMN IF NOT EXISTS liquido_apurado_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER allocated_cost_brl,
  ADD COLUMN IF NOT EXISTS comissao_individual_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER liquido_apurado_brl,
  ADD COLUMN IF NOT EXISTS bonus_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER comissao_individual_brl,
  ADD COLUMN IF NOT EXISTS comissao_final_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER bonus_brl;

-- Add missing cost and metadata fields to comissoes_resumo used by persistMonthlySummary
ALTER TABLE comissoes_resumo
  ADD COLUMN IF NOT EXISTS team_cost_total DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER team_cost_settings_rate,
  ADD COLUMN IF NOT EXISTS equal_cost_share_per_active_seller DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER team_cost_total,
  ADD COLUMN IF NOT EXISTS explicit_cost_share_per_non_trainee DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER equal_cost_share_per_active_seller,
  ADD COLUMN IF NOT EXISTS team_cost_fixed_usd DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER explicit_cost_share_per_non_trainee,
  ADD COLUMN IF NOT EXISTS team_cost_percent_rate DECIMAL(10,6) NOT NULL DEFAULT 0 AFTER team_cost_fixed_usd,
  ADD COLUMN IF NOT EXISTS team_cost_percent_total DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER team_cost_percent_rate,
  ADD COLUMN IF NOT EXISTS team_remaining_cost_to_cover DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER team_cost_percent_total,
  ADD COLUMN IF NOT EXISTS apply_bonus TINYINT(1) NOT NULL DEFAULT 0 AFTER team_remaining_cost_to_cover,
  ADD COLUMN IF NOT EXISTS active_count INT NOT NULL DEFAULT 0 AFTER apply_bonus,
  ADD COLUMN IF NOT EXISTS active_cost_split_count INT NOT NULL DEFAULT 0 AFTER active_count,
  ADD COLUMN IF NOT EXISTS non_trainee_active_count INT NOT NULL DEFAULT 0 AFTER active_cost_split_count,
  ADD COLUMN IF NOT EXISTS bonus_rate DECIMAL(10,6) NOT NULL DEFAULT 0 AFTER non_trainee_active_count,
  ADD COLUMN IF NOT EXISTS team_bruto_total_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER bonus_rate,
  ADD COLUMN IF NOT EXISTS meta_equipe_brl DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER team_bruto_total_brl;

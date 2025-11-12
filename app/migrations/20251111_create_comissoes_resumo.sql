-- Persisted monthly team summary for commissions/cash
CREATE TABLE IF NOT EXISTS comissoes_resumo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  periodo VARCHAR(7) NOT NULL, -- YYYY-MM
  team_bruto_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  team_liquido_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  company_cash_usd DECIMAL(18,2) NOT NULL DEFAULT 0,
  sum_rateado_usd DECIMAL(18,2) NOT NULL DEFAULT 0,
  sum_commissions_usd DECIMAL(18,2) NOT NULL DEFAULT 0,
  company_cash_brl DECIMAL(18,2) NOT NULL DEFAULT 0,
  sum_rateado_brl DECIMAL(18,2) NOT NULL DEFAULT 0,
  sum_commissions_brl DECIMAL(18,2) NOT NULL DEFAULT 0,
  usd_rate DECIMAL(18,6) NOT NULL DEFAULT 0,
  team_cost_settings_rate DECIMAL(10,6) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_comissoes_resumo_periodo (periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

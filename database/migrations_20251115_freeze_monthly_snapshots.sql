-- Migração: Tabela de snapshots mensais (congelamento de períodos 10->9)

CREATE TABLE IF NOT EXISTS monthly_snapshots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  period_key VARCHAR(32) NOT NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,

  scope ENUM('company','seller') NOT NULL DEFAULT 'company',
  seller_id INT NULL,
  seller_name VARCHAR(191) NULL,
  seller_email VARCHAR(191) NULL,
  seller_role VARCHAR(50) NULL,
  seller_ativo TINYINT(1) NULL,

  active_users INT NULL,
  sales_count INT NULL,
  atendimentos INT NULL,
  atendimentos_concluidos INT NULL,

  bruto_total_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  liquido_total_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  liquido_apurado_usd DECIMAL(15,2) NOT NULL DEFAULT 0,

  custos_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  custos_percentuais DECIMAL(15,4) NOT NULL DEFAULT 0,
  custo_config_rate DECIMAL(10,4) NOT NULL DEFAULT 0,
  team_cost_total_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  lucro_liquido_usd DECIMAL(15,2) NOT NULL DEFAULT 0,

  comissao_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  comissao_brl DECIMAL(15,2) NOT NULL DEFAULT 0,
  company_cash_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  company_cash_brl DECIMAL(15,2) NOT NULL DEFAULT 0,

  usd_rate DECIMAL(10,4) NOT NULL DEFAULT 0,
  meta_equipe_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
  meta_equipe_brl DECIMAL(15,2) NOT NULL DEFAULT 0,
  meta_atingida TINYINT(1) NOT NULL DEFAULT 0,

  prolabore_pct DECIMAL(10,4) NOT NULL DEFAULT 0,
  prolabore_usd DECIMAL(15,2) NOT NULL DEFAULT 0,

  frozen_by_user_id INT NULL,
  frozen_by_user_name VARCHAR(191) NULL,
  frozen_source VARCHAR(20) NOT NULL DEFAULT 'manual',

  extra_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idx_period (period_from, period_to),
  KEY idx_scope (scope, seller_id),
  KEY idx_period_key (period_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

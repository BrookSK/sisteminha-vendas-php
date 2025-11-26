ALTER TABLE simulator_budgets
  ADD COLUMN paid TINYINT(1) NOT NULL DEFAULT 0 AFTER data_json,
  ADD COLUMN paid_at DATETIME NULL AFTER paid,
  ADD INDEX idx_sim_budgets_paid (paid),
  ADD INDEX idx_sim_budgets_paid_at (paid_at);

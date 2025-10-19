-- Unified approvals table for trainee flows
CREATE TABLE IF NOT EXISTS approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NULL,
  action ENUM('create','update') NOT NULL,
  payload JSON NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_by INT NOT NULL,
  reviewer_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  INDEX idx_approvals_entity (entity_type, entity_id),
  INDEX idx_approvals_status (status),
  INDEX idx_approvals_created_by (created_by),
  INDEX idx_approvals_reviewer (reviewer_id),
  CONSTRAINT fk_approvals_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_approvals_reviewer FOREIGN KEY (reviewer_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

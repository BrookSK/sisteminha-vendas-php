-- Hostings table
CREATE TABLE IF NOT EXISTS hostings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(60) NOT NULL, -- Hosttools, Plesk, Hostinger, etc.
  server_name VARCHAR(120) NOT NULL,
  plan_name VARCHAR(120) DEFAULT NULL,
  price DECIMAL(10,2) DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  due_day TINYINT DEFAULT NULL,
  billing_cycle ENUM('mensal','bimestral','trimestral','semestral','anual','bienal','trienal','outro') DEFAULT 'mensal',
  server_ip VARCHAR(45) DEFAULT NULL,
  auto_payment TINYINT(1) DEFAULT 0,
  login_email VARCHAR(150) DEFAULT NULL,
  payer_responsible VARCHAR(120) DEFAULT NULL,
  status ENUM('ativo','em_contratacao_recusada','nao_contratado','em_cancelamento','em_contratacao','cancelado') DEFAULT 'ativo',
  description TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  updated_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_hostings_provider (provider),
  INDEX idx_hostings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

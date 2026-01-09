CREATE TABLE IF NOT EXISTS simulator_webhook_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  external_id VARCHAR(190) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  image_url VARCHAR(500) NULL,
  store_id INT NULL,
  store_name VARCHAR(150) NULL,
  qtd INT NOT NULL DEFAULT 0,
  peso_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
  links_json LONGTEXT NULL,
  event_date DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_sim_wh_products_external_id (external_id),
  INDEX idx_sim_wh_products_event_date (event_date),
  INDEX idx_sim_wh_products_store_id (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

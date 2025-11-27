ALTER TABLE simulator_products
  ADD COLUMN store_id INT NULL AFTER peso_kg,
  ADD INDEX idx_simprod_store (store_id);

CREATE TABLE IF NOT EXISTS simulator_stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_simstore_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

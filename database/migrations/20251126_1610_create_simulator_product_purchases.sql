CREATE TABLE IF NOT EXISTS simulator_product_purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_key VARCHAR(190) NOT NULL,
  purchased_qtd INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_sim_prod_purchases_product_key (product_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

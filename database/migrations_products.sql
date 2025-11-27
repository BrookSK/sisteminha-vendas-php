-- Tabela principal de produtos do simulador
CREATE TABLE IF NOT EXISTS simulator_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(100) NULL,
  nome VARCHAR(255) NOT NULL,
  marca VARCHAR(150) NULL,
  image_url VARCHAR(500) NULL,
  peso_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  INDEX idx_simprod_nome (nome),
  INDEX idx_simprod_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Links onde comprar cada produto (1:N)
CREATE TABLE IF NOT EXISTS simulator_product_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  url VARCHAR(500) NOT NULL,
  fonte VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (product_id),
  CONSTRAINT fk_spl_product FOREIGN KEY (product_id) REFERENCES simulator_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

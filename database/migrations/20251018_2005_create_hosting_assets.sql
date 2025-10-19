-- Hosting assets: sites, systems, emails
CREATE TABLE IF NOT EXISTS hosting_assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  url VARCHAR(255) DEFAULT NULL,
  hosting_id INT DEFAULT NULL,
  type ENUM('site','sistema','email') NOT NULL DEFAULT 'site',
  server_ip VARCHAR(45) DEFAULT NULL,
  real_ip VARCHAR(45) DEFAULT NULL,
  pointing_ok TINYINT(1) DEFAULT NULL,
  client_id INT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  updated_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_hosting_assets_hosting FOREIGN KEY (hosting_id) REFERENCES hostings(id) ON DELETE SET NULL,
  INDEX idx_assets_title (title),
  INDEX idx_assets_type (type),
  INDEX idx_assets_hosting (hosting_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

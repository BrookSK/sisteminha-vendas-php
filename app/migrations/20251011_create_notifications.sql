-- Migration: create internal notifications tables
-- Run this in MySQL/MariaDB

START TRANSACTION;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'Informação',
  status VARCHAR(20) NOT NULL DEFAULT 'ativa',
  created_by INT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_created_at (created_at),
  INDEX idx_type (type),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_recipients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notification_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR(20) NOT NULL,
  read_at DATETIME NULL,
  archived_at DATETIME NULL,
  deleted_at DATETIME NULL,
  UNIQUE KEY uk_notif_user (notification_id, user_id),
  INDEX idx_user (user_id),
  CONSTRAINT fk_nr_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

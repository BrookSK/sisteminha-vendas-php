-- Documentations & Procedures module
START TRANSACTION;

CREATE TABLE IF NOT EXISTS documentation_areas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_area_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documentations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  status ENUM('concluida','nao_iniciada','em_andamento','em_revisao','arquivada') NOT NULL DEFAULT 'nao_iniciada',
  project_id INT NULL,
  area_id INT NULL,
  internal_visibility ENUM('admin','manager','seller','all') NOT NULL DEFAULT 'all',
  published TINYINT(1) NOT NULL DEFAULT 0,
  external_slug VARCHAR(120) NULL UNIQUE,
  content LONGTEXT NULL,
  created_by INT NULL,
  updated_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  INDEX idx_docs_status (status),
  INDEX idx_docs_area (area_id),
  INDEX idx_docs_project (project_id),
  CONSTRAINT fk_docs_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_docs_area FOREIGN KEY (area_id) REFERENCES documentation_areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_docs_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_docs_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documentation_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  documentation_id INT NOT NULL,
  user_id INT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doc_comments_doc (documentation_id),
  INDEX idx_doc_comments_user (user_id),
  CONSTRAINT fk_doc_comments_doc FOREIGN KEY (documentation_id) REFERENCES documentations(id) ON DELETE CASCADE,
  CONSTRAINT fk_doc_comments_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documentation_email_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  documentation_id INT NOT NULL,
  email VARCHAR(200) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_doc_email (documentation_id, email),
  INDEX idx_doc_email_doc (documentation_id),
  CONSTRAINT fk_doc_email_doc FOREIGN KEY (documentation_id) REFERENCES documentations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

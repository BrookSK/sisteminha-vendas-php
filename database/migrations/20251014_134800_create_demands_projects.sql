-- Demands & Projects module
-- Create tables: projects, project_members, demands, demand_comments, demand_files, time_off

START TRANSACTION;

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  status ENUM('nao_iniciada','em_construcao','aguardando','em_teste','em_revisao','em_manutencao','cancelado','finalizado') NOT NULL DEFAULT 'nao_iniciada',
  start_date DATE NOT NULL,
  due_date DATE NOT NULL,
  description TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  INDEX idx_projects_status (status),
  INDEX idx_projects_dates (start_date, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_user (project_id, user_id),
  INDEX idx_project_members_project (project_id),
  INDEX idx_project_members_user (user_id),
  CONSTRAINT fk_pm_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  type_desc VARCHAR(80) NOT NULL,
  assignee_id INT NULL,
  project_id INT NULL,
  status ENUM('pendente','ideia','trabalhando','estimativa','recusado','aguardando','entregue','arquivado') NOT NULL DEFAULT 'pendente',
  due_date DATE NULL,
  priority ENUM('baixa','media','alta','urgente','ideia') NOT NULL DEFAULT 'baixa',
  classification ENUM('erro_garantia','ajuste_operacional','alteracao_evolutiva','orcamento_estimativa') NULL,
  details TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  INDEX idx_demands_status (status),
  INDEX idx_demands_due (due_date),
  INDEX idx_demands_assignee (assignee_id),
  INDEX idx_demands_project (project_id),
  INDEX idx_demands_priority (priority),
  CONSTRAINT fk_demands_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_demands_assignee FOREIGN KEY (assignee_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_demands_creator FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demand_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  demand_id INT NOT NULL,
  user_id INT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dc_demand (demand_id),
  INDEX idx_dc_user (user_id),
  CONSTRAINT fk_dc_demand FOREIGN KEY (demand_id) REFERENCES demands(id) ON DELETE CASCADE,
  CONSTRAINT fk_dc_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demand_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  demand_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime VARCHAR(120) NULL,
  size_bytes INT NULL,
  uploaded_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_df_demand (demand_id),
  CONSTRAINT fk_df_demand FOREIGN KEY (demand_id) REFERENCES demands(id) ON DELETE CASCADE,
  CONSTRAINT fk_df_uploader FOREIGN KEY (uploaded_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS time_off (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  reason VARCHAR(200) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_timeoff_date (date),
  INDEX idx_timeoff_user (user_id),
  CONSTRAINT fk_timeoff_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

-- Add supervisor relationship to usuarios
ALTER TABLE usuarios
  ADD COLUMN supervisor_user_id INT NULL AFTER ativo,
  ADD INDEX idx_usuarios_supervisor (supervisor_user_id),
  ADD CONSTRAINT fk_usuarios_supervisor FOREIGN KEY (supervisor_user_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE;

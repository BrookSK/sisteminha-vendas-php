-- Ensure external id for webhook upserts
ALTER TABLE vendas_internacionais
  ADD COLUMN IF NOT EXISTS id_externo VARCHAR(64) NULL,
  ADD UNIQUE KEY IF NOT EXISTS ux_vi_id_externo (id_externo);

ALTER TABLE vendas_nacionais
  ADD COLUMN IF NOT EXISTS id_externo VARCHAR(64) NULL,
  ADD UNIQUE KEY IF NOT EXISTS ux_vn_id_externo (id_externo);

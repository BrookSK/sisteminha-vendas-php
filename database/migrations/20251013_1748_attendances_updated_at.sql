-- Add updated timestamp for attendances edits
ALTER TABLE atendimentos
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL;

-- Fix unique constraint for attendances so each user has their own daily row
-- Root cause: previous schema had UNIQUE KEY on (data) only, causing overwrites between users

-- Drop old unique index if it exists
ALTER TABLE atendimentos
  DROP INDEX uniq_data;

-- Create the correct composite unique index (date + user)
ALTER TABLE atendimentos
  ADD UNIQUE KEY uniq_data_user (data, usuario_id);

-- Optional: if you want faster date filtering, keep a separate non-unique index on data
-- (safe to run if not present; MySQL will error if duplicate name, so use IF NOT EXISTS pattern only if your server supports it)
-- CREATE INDEX idx_atendimentos_data ON atendimentos (data);

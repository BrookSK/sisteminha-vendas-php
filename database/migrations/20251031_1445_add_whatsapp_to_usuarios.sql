-- Add optional WhatsApp field to usuarios
ALTER TABLE usuarios
  ADD COLUMN whatsapp VARCHAR(32) NULL AFTER supervisor_user_id;

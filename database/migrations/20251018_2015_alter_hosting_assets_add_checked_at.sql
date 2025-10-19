-- Add checked_at to hosting_assets for last DNS verification timestamp
ALTER TABLE hosting_assets
  ADD COLUMN checked_at DATETIME NULL AFTER updated_at;

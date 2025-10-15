-- Migration: add multi-suite columns to clientes
-- Created at: 2025-10-11 14:00:00-03:00

START TRANSACTION;

ALTER TABLE clientes
  ADD COLUMN suite_br VARCHAR(50) NULL AFTER suite,
  ADD COLUMN suite_us VARCHAR(50) NULL AFTER suite_br,
  ADD COLUMN suite_red VARCHAR(50) NULL AFTER suite_us,
  ADD COLUMN suite_globe VARCHAR(50) NULL AFTER suite_red;

-- Optional backfill from legacy `suite` when pattern matches
-- Populate numeric part for each prefix (accepts with or without dash)
UPDATE clientes SET suite_br =
  CASE
    WHEN suite LIKE 'BR-%' THEN SUBSTRING(suite, 4)
    WHEN suite LIKE 'BR%' THEN SUBSTRING(suite, 3)
    ELSE suite_br
  END
WHERE (suite REGEXP '^BR-?[0-9]+' ) AND (suite_br IS NULL OR suite_br = '');

UPDATE clientes SET suite_us =
  CASE
    WHEN suite LIKE 'US-%' THEN SUBSTRING(suite, 4)
    WHEN suite LIKE 'US%' THEN SUBSTRING(suite, 3)
    ELSE suite_us
  END
WHERE (suite REGEXP '^US-?[0-9]+' ) AND (suite_us IS NULL OR suite_us = '');

UPDATE clientes SET suite_red =
  CASE
    WHEN suite LIKE 'RED-%' THEN SUBSTRING(suite, 5)
    WHEN suite LIKE 'RED%' THEN SUBSTRING(suite, 4)
    ELSE suite_red
  END
WHERE (suite REGEXP '^RED-?[0-9]+' ) AND (suite_red IS NULL OR suite_red = '');

UPDATE clientes SET suite_globe =
  CASE
    WHEN suite LIKE 'GLOBE-%' THEN SUBSTRING(suite, 7)
    WHEN suite LIKE 'GLOBE%' THEN SUBSTRING(suite, 6)
    ELSE suite_globe
  END
WHERE (suite REGEXP '^GLOBE-?[0-9]+' ) AND (suite_globe IS NULL OR suite_globe = '');

COMMIT;

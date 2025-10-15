-- Migration: backfill suite_globe from legacy 'GLOB' prefix in clientes.suite
-- Created at: 2025-10-11 14:08:00-03:00

START TRANSACTION;

-- Populate numeric part for GLOB-prefixed suites when suite_globe is empty
UPDATE clientes SET suite_globe =
  CASE
    WHEN suite LIKE 'GLOB-%' THEN SUBSTRING(suite, 6)
    WHEN suite LIKE 'GLOB%' THEN SUBSTRING(suite, 5)
    ELSE suite_globe
  END
WHERE (suite REGEXP '^GLOB-?[0-9]+') AND (suite_globe IS NULL OR suite_globe = '');

COMMIT;

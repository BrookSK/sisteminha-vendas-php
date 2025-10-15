-- Ensure unique suites per prefix and legacy in clientes

-- 1) Normalize blanks to NULL to avoid duplicate '' violating UNIQUE constraints
UPDATE clientes SET suite = NULL WHERE suite = '' OR suite IS NULL;
UPDATE clientes SET suite_br = NULL WHERE suite_br = '' OR suite_br IS NULL;
UPDATE clientes SET suite_us = NULL WHERE suite_us = '' OR suite_us IS NULL;
UPDATE clientes SET suite_red = NULL WHERE suite_red = '' OR suite_red IS NULL;
UPDATE clientes SET suite_globe = NULL WHERE suite_globe = '' OR suite_globe IS NULL;

-- 2) Create unique indexes (remove IF NOT EXISTS for broader MySQL compatibility)
--    NOTE: If an index already exists, this statement will fail; create only once.
CREATE UNIQUE INDEX idx_clientes_suite       ON clientes (suite);
CREATE UNIQUE INDEX idx_clientes_suite_br    ON clientes (suite_br);
CREATE UNIQUE INDEX idx_clientes_suite_us    ON clientes (suite_us);
CREATE UNIQUE INDEX idx_clientes_suite_red   ON clientes (suite_red);
CREATE UNIQUE INDEX idx_clientes_suite_globe ON clientes (suite_globe);

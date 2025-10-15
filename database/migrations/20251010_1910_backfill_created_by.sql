-- Migration: backfill clientes.created_by using vendas ownership heuristics
-- Strategy:
-- - Prefer the most recent sale's user for each client (from international, national, then legacy vendas)
-- - If no sales found, leave NULL

START TRANSACTION;

-- Temporary table to compute preferred owner per client
CREATE TEMPORARY TABLE tmp_client_owner (
  cliente_id INT PRIMARY KEY,
  owner_id INT
) ENGINE=Memory;

-- 1) From vendas_internacionais (prefer newest)
INSERT INTO tmp_client_owner (cliente_id, owner_id)
SELECT t.cliente_id, t.vendedor_id AS owner_id
FROM (
  SELECT vi.cliente_id, vi.vendedor_id,
         ROW_NUMBER() OVER (PARTITION BY vi.cliente_id ORDER BY vi.data_lancamento DESC, vi.id DESC) AS rn
  FROM vendas_internacionais vi
) t
WHERE t.rn = 1
ON DUPLICATE KEY UPDATE owner_id = VALUES(owner_id);

-- 2) From vendas_nacionais where not set yet
INSERT INTO tmp_client_owner (cliente_id, owner_id)
SELECT t.cliente_id, t.vendedor_id AS owner_id
FROM (
  SELECT vn.cliente_id, vn.vendedor_id,
         ROW_NUMBER() OVER (PARTITION BY vn.cliente_id ORDER BY vn.data_lancamento DESC, vn.id DESC) AS rn
  FROM vendas_nacionais vn
) t
WHERE t.rn = 1
ON DUPLICATE KEY UPDATE owner_id = COALESCE(tmp_client_owner.owner_id, VALUES(owner_id));

-- 3) From legacy vendas table if still exists
-- Note: some MySQL versions may need conditional existence checks; ignore errors if table not present
INSERT INTO tmp_client_owner (cliente_id, owner_id)
SELECT t.cliente_id, t.usuario_id AS owner_id
FROM (
  SELECT v.cliente_id, v.usuario_id,
         ROW_NUMBER() OVER (PARTITION BY v.cliente_id ORDER BY v.created_at DESC, v.id DESC) AS rn
  FROM vendas v
) t
WHERE t.rn = 1
ON DUPLICATE KEY UPDATE owner_id = COALESCE(tmp_client_owner.owner_id, VALUES(owner_id));

-- Apply backfill to clientes
UPDATE clientes c
JOIN tmp_client_owner o ON o.cliente_id = c.id
SET c.created_by = o.owner_id
WHERE c.created_by IS NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_client_owner;

COMMIT;

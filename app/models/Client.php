<?php
namespace Models;

use Core\Model;
use PDO;

class Client extends Model
{
    private function hasMultiSuites(): bool
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM clientes LIKE 'suite_br'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($col);
        } catch (\Throwable $e) {
            return false;
        }
    }
    private function hasOwnerColumn(): bool
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM clientes LIKE 'created_by'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($col);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function search(?string $q = null, int $limit = 20, int $offset = 0, ?int $ownerId = null, ?string $sort = null): array
    {
        $hasOwner = $this->hasOwnerColumn();
        $hasMulti = $this->hasMultiSuites();
        $conds = [];$vals = [];
        if ($q) {
            $or = [];
            $like = "%$q%";
            $or[] = '(c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ? OR UPPER(c.suite) LIKE ?)';
            $vals[] = $like; $vals[] = $like; $vals[] = $like; $vals[] = "%".strtoupper($q)."%";
            if ($hasMulti) {
                // Support BR-123, US123, RED 123, GLOB-123
                if (preg_match('/^(BR|US|RED|GLOB)[- ]?(\d+)$/i', $q, $m)) {
                    $prefix = strtoupper($m[1]);
                    $num = $m[2];
                    if ($prefix === 'BR') { $or[] = 'c.suite_br = ?'; $vals[] = $num; }
                    if ($prefix === 'US') { $or[] = 'c.suite_us = ?'; $vals[] = $num; }
                    if ($prefix === 'RED') { $or[] = 'c.suite_red = ?'; $vals[] = $num; }
                    if ($prefix === 'GLOB') { $or[] = 'c.suite_globe = ?'; $vals[] = $num; }
                } else if (preg_match('/^\d+$/', $q)) {
                    // Numeric only: match any per-site suite equal to this number
                    $or[] = '(c.suite_br = ? OR c.suite_us = ? OR c.suite_red = ? OR c.suite_globe = ?)';
                    $vals[] = $q; $vals[] = $q; $vals[] = $q; $vals[] = $q;
                }
            }
            $conds[] = '('.implode(' OR ', $or).')';
        }
        if ($hasOwner && $ownerId) {
            $conds[] = 'c.created_by = ?';
            $vals[] = (int)$ownerId;
        }
        $lim = max(1, (int)$limit);
        $off = max(0, (int)$offset);
        $sql = "SELECT c.*, (
                COALESCE((SELECT COUNT(*) FROM vendas v WHERE v.cliente_id = c.id),0)
                + COALESCE((SELECT COUNT(*) FROM vendas_internacionais vi WHERE vi.cliente_id = c.id),0)
                + COALESCE((SELECT COUNT(*) FROM vendas_nacionais vn WHERE vn.cliente_id = c.id),0)
            ) as total_vendas FROM clientes c";
        if ($conds) { $sql .= ' WHERE '.implode(' AND ', $conds); }
        // Whitelist sorting
        $orderBy = 'c.created_at DESC';
        switch ($sort) {
            case 'created_at_asc': $orderBy = 'c.created_at ASC'; break;
            case 'nome_asc': $orderBy = 'c.nome ASC'; break;
            case 'nome_desc': $orderBy = 'c.nome DESC'; break;
            case 'total_vendas_desc': $orderBy = 'total_vendas DESC, c.created_at DESC'; break;
            case 'total_vendas_asc': $orderBy = 'total_vendas ASC, c.created_at DESC'; break;
            default: /* created_at_desc */ $orderBy = 'c.created_at DESC';
        }
        $sql .= ' ORDER BY ' . $orderBy . ' LIMIT ' . $lim . ' OFFSET ' . $off;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Lightweight search for autocomplete: avoids COUNT subqueries for speed/compat. */
    public function searchLite(?string $q = null, int $limit = 20, int $offset = 0, ?int $ownerId = null): array
    {
        $hasOwner = $this->hasOwnerColumn();
        $hasMulti = $this->hasMultiSuites();
        $conds = [];$vals = [];
        if ($q) {
            $or = [];
            $like = "%$q%";
            $or[] = '(c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ? OR UPPER(c.suite) LIKE ?)';
            $vals[] = $like; $vals[] = $like; $vals[] = $like; $vals[] = "%".strtoupper($q)."%";
            if ($hasMulti) {
                if (preg_match('/^(BR|US|RED|GLOB)[- ]?(\d+)$/i', $q, $m)) {
                    $prefix = strtoupper($m[1]);
                    $num = $m[2];
                    if ($prefix === 'BR') { $or[] = 'c.suite_br = ?'; $vals[] = $num; }
                    if ($prefix === 'US') { $or[] = 'c.suite_us = ?'; $vals[] = $num; }
                    if ($prefix === 'RED') { $or[] = 'c.suite_red = ?'; $vals[] = $num; }
                    if ($prefix === 'GLOB') { $or[] = 'c.suite_globe = ?'; $vals[] = $num; }
                } else if (preg_match('/^\d+$/', $q)) {
                    $or[] = '(c.suite_br = ? OR c.suite_us = ? OR c.suite_red = ? OR c.suite_globe = ?)';
                    $vals[] = $q; $vals[] = $q; $vals[] = $q; $vals[] = $q;
                }
            }
            $conds[] = '('.implode(' OR ', $or).')';
        }
        if ($hasOwner && $ownerId) {
            $conds[] = 'c.created_by = ?';
            $vals[] = (int)$ownerId;
        }
        $lim = max(1, (int)$limit);
        $off = max(0, (int)$offset);
        $sql = 'SELECT c.id, c.nome, c.email, c.telefone, c.suite' . ($hasMulti ? ', c.suite_br, c.suite_us, c.suite_red, c.suite_globe' : '') . ' FROM clientes c';
        if ($conds) { $sql .= ' WHERE '.implode(' AND ', $conds); }
        $sql .= ' ORDER BY c.created_at DESC LIMIT ' . $lim . ' OFFSET ' . $off;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(?string $q = null): int
    {
        if ($q) {
            $hasMulti = $this->hasMultiSuites();
            $pieces = [];$vals = [];
            $pieces[] = '(nome LIKE ? OR email LIKE ? OR telefone LIKE ? OR UPPER(suite) LIKE ?)';
            $like = "%$q%";
            $vals[] = $like; $vals[] = $like; $vals[] = $like; $vals[] = "%".strtoupper($q)."%";
            if ($hasMulti) {
                if (preg_match('/^(BR|US|RED|GLOB)[- ]?(\d+)$/i', $q, $m)) {
                    $prefix = strtoupper($m[1]);
                    $num = $m[2];
                    if ($prefix === 'BR') { $pieces[] = 'suite_br = ?'; $vals[] = $num; }
                    if ($prefix === 'US') { $pieces[] = 'suite_us = ?'; $vals[] = $num; }
                    if ($prefix === 'RED') { $pieces[] = 'suite_red = ?'; $vals[] = $num; }
                    if ($prefix === 'GLOB') { $pieces[] = 'suite_globe = ?'; $vals[] = $num; }
                } else if (preg_match('/^\d+$/', $q)) {
                    $pieces[] = '(suite_br = ? OR suite_us = ? OR suite_red = ? OR suite_globe = ?)';
                    $vals[] = $q; $vals[] = $q; $vals[] = $q; $vals[] = $q;
                }
            }
            $sql = 'SELECT COUNT(*) as c FROM clientes WHERE ' . implode(' OR ', $pieces);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($vals);
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) as c FROM clientes');
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $hasOwner = $this->hasOwnerColumn();
        $hasMulti = $this->hasMultiSuites();
        if ($hasOwner) {
            if ($hasMulti) {
                $stmt = $this->db->prepare('INSERT INTO clientes (nome, email, telefone, suite, suite_br, suite_us, suite_red, suite_globe, endereco, observacoes, created_by, created_at) VALUES
                    (:nome, :email, :telefone, :suite, :suite_br, :suite_us, :suite_red, :suite_globe, :endereco, :observacoes, :created_by, NOW())');
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':email' => $data['email'] ?? null,
                    ':telefone' => $data['telefone'] ?? null,
                    ':suite' => $data['suite'] ?? null,
                    ':suite_br' => $data['suite_br'] ?? null,
                    ':suite_us' => $data['suite_us'] ?? null,
                    ':suite_red' => $data['suite_red'] ?? null,
                    ':suite_globe' => $data['suite_globe'] ?? null,
                    ':endereco' => $data['endereco'] ?? null,
                    ':observacoes' => $data['observacoes'] ?? null,
                    ':created_by' => $data['created_by'] ?? null,
                ]);
            } else {
                $stmt = $this->db->prepare('INSERT INTO clientes (nome, email, telefone, suite, endereco, observacoes, created_by, created_at) VALUES
                    (:nome, :email, :telefone, :suite, :endereco, :observacoes, :created_by, NOW())');
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':email' => $data['email'] ?? null,
                    ':telefone' => $data['telefone'] ?? null,
                    ':suite' => $data['suite'] ?? null,
                    ':endereco' => $data['endereco'] ?? null,
                    ':observacoes' => $data['observacoes'] ?? null,
                    ':created_by' => $data['created_by'] ?? null,
                ]);
            }
        } else {
            if ($hasMulti) {
                $stmt = $this->db->prepare('INSERT INTO clientes (nome, email, telefone, suite, suite_br, suite_us, suite_red, suite_globe, endereco, observacoes, created_at) VALUES
                    (:nome, :email, :telefone, :suite, :suite_br, :suite_us, :suite_red, :suite_globe, :endereco, :observacoes, NOW())');
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':email' => $data['email'] ?? null,
                    ':telefone' => $data['telefone'] ?? null,
                    ':suite' => $data['suite'] ?? null,
                    ':suite_br' => $data['suite_br'] ?? null,
                    ':suite_us' => $data['suite_us'] ?? null,
                    ':suite_red' => $data['suite_red'] ?? null,
                    ':suite_globe' => $data['suite_globe'] ?? null,
                    ':endereco' => $data['endereco'] ?? null,
                    ':observacoes' => $data['observacoes'] ?? null,
                ]);
            } else {
                $stmt = $this->db->prepare('INSERT INTO clientes (nome, email, telefone, suite, endereco, observacoes, created_at) VALUES
                    (:nome, :email, :telefone, :suite, :endereco, :observacoes, NOW())');
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':email' => $data['email'] ?? null,
                    ':telefone' => $data['telefone'] ?? null,
                    ':suite' => $data['suite'] ?? null,
                    ':endereco' => $data['endereco'] ?? null,
                    ':observacoes' => $data['observacoes'] ?? null,
                ]);
            }
        }
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $hasMulti = $this->hasMultiSuites();
        if ($hasMulti) {
            $stmt = $this->db->prepare('UPDATE clientes SET nome=:nome, email=:email, telefone=:telefone, suite=:suite, suite_br=:suite_br, suite_us=:suite_us, suite_red=:suite_red, suite_globe=:suite_globe, endereco=:endereco, observacoes=:observacoes WHERE id = :id');
            $stmt->execute([
                ':id' => $id,
                ':nome' => $data['nome'],
                ':email' => $data['email'] ?? null,
                ':telefone' => $data['telefone'] ?? null,
                ':suite' => $data['suite'] ?? null,
                ':suite_br' => $data['suite_br'] ?? null,
                ':suite_us' => $data['suite_us'] ?? null,
                ':suite_red' => $data['suite_red'] ?? null,
                ':suite_globe' => $data['suite_globe'] ?? null,
                ':endereco' => $data['endereco'] ?? null,
                ':observacoes' => $data['observacoes'] ?? null,
            ]);
        } else {
            $stmt = $this->db->prepare('UPDATE clientes SET nome=:nome, email=:email, telefone=:telefone, suite=:suite, endereco=:endereco, observacoes=:observacoes WHERE id = :id');
            $stmt->execute([
                ':id' => $id,
                ':nome' => $data['nome'],
                ':email' => $data['email'] ?? null,
                ':telefone' => $data['telefone'] ?? null,
                ':suite' => $data['suite'] ?? null,
                ':endereco' => $data['endereco'] ?? null,
                ':observacoes' => $data['observacoes'] ?? null,
            ]);
        }
    }

    public function delete(int $id): void
    {
        if ($this->isPlaceholderId($id)) { return; }
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function hasRelatedSales(int $id): array
    {
        $counts = ['vendas'=>0,'vendas_internacionais'=>0,'vendas_nacionais'=>0];
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) c FROM vendas WHERE cliente_id = :id');
            $stmt->execute([':id'=>$id]);
            $counts['vendas'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {}
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) c FROM vendas_internacionais WHERE cliente_id = :id');
            $stmt->execute([':id'=>$id]);
            $counts['vendas_internacionais'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {}
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) c FROM vendas_nacionais WHERE cliente_id = :id');
            $stmt->execute([':id'=>$id]);
            $counts['vendas_nacionais'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {}
        return $counts;
    }

    public function detachSales(int $id): void
    {
        // Try to set cliente_id to NULL; if FK/NOT NULL blocks, fallback to placeholder client
        foreach (['vendas','vendas_internacionais','vendas_nacionais'] as $tbl) {
            try {
                $sql = "UPDATE $tbl SET cliente_id = NULL WHERE cliente_id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id'=>$id]);
            } catch (\Throwable $e) {
                try {
                    $placeholderId = $this->ensurePlaceholderClient();
                    $sql = "UPDATE $tbl SET cliente_id = :pid WHERE cliente_id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([':pid'=>$placeholderId, ':id'=>$id]);
                } catch (\Throwable $e2) {
                    // Give up silently to avoid breaking; delete() will still fail if rows remain
                }
            }
        }
    }

    private function ensurePlaceholderClient(): int
    {
        // Reuse or create a special client to receive historical sales
        $email = 'removed@system.local';
        $name = '[REMOVIDO]';
        try {
            $stmt = $this->db->prepare('SELECT id FROM clientes WHERE email = :email LIMIT 1');
            $stmt->execute([':email'=>$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['id'] > 0) return (int)$row['id'];
        } catch (\Throwable $e) {
            // ignore and try find by name
        }
        try {
            $stmt = $this->db->prepare('SELECT id FROM clientes WHERE nome = :nome LIMIT 1');
            $stmt->execute([':nome'=>$name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['id'] > 0) return (int)$row['id'];
        } catch (\Throwable $e) {
            // ignore
        }
        // Create minimal row (columns not-null beyond nome/email may exist; set safe defaults)
        try {
            $stmt = $this->db->prepare('INSERT INTO clientes (nome, email, created_at) VALUES (:nome, :email, NOW())');
            $stmt->execute([':nome'=>$name, ':email'=>$email]);
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            // Fallback: attempt insert with only nome
            $stmt = $this->db->prepare('INSERT INTO clientes (nome, created_at) VALUES (:nome, NOW())');
            $stmt->execute([':nome'=>$name]);
            return (int)$this->db->lastInsertId();
        }
    }

    public function deleteForce(int $id): void
    {
        if ($this->isPlaceholderId($id)) { return; }
        $this->detachSales($id);
        $this->delete($id);
    }

    public function isOwner(int $userId, array $clientRow): bool
    {
        if ($userId <= 0) return false;
        if ($this->hasOwnerColumn()) {
            return (int)($clientRow['created_by'] ?? 0) === $userId;
        }
        // If there is no ownership column, we cannot enforce; allow by default to avoid breaking usage
        return true;
    }

    private function isPlaceholderId(int $id): bool
    {
        $row = $this->find($id);
        if (!$row) return false;
        return $this->isPlaceholderRow($row);
    }

    private function isPlaceholderRow(array $row): bool
    {
        $name = trim((string)($row['nome'] ?? ''));
        $email = strtolower(trim((string)($row['email'] ?? '')));
        if ($name === '[REMOVIDO]') return true;
        if ($email === 'removed@system.local') return true;
        return false;
    }
}

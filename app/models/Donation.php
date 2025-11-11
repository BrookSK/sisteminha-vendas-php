<?php
namespace Models;

use Core\Model;
use PDO;

class Donation extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM doacoes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data, ?int $userId): int
    {
        $stmt = $this->db->prepare('INSERT INTO doacoes (instituicao, cnpj, descricao, valor_brl, data, categoria, status, criado_por, created_at)
            VALUES (:instituicao, :cnpj, :descricao, :valor_brl, :data, :categoria, "ativo", :uid, NOW())');
        $stmt->execute([
            ':instituicao' => $data['instituicao'],
            ':cnpj' => $data['cnpj'] ?? null,
            ':descricao' => $data['descricao'] ?? null,
            ':valor_brl' => (float)$data['valor_brl'],
            ':data' => $data['data'],
            ':categoria' => $data['categoria'] ?? null,
            ':uid' => $userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE doacoes SET instituicao=:instituicao, cnpj=:cnpj, descricao=:descricao, valor_brl=:valor_brl, data=:data, categoria=:categoria, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            ':id' => $id,
            ':instituicao' => $data['instituicao'],
            ':cnpj' => $data['cnpj'] ?? null,
            ':descricao' => $data['descricao'] ?? null,
            ':valor_brl' => (float)$data['valor_brl'],
            ':data' => $data['data'],
            ':categoria' => $data['categoria'] ?? null,
        ]);
    }

    public function cancel(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE doacoes SET status="cancelado", updated_at=NOW() WHERE id=:id');
        $stmt->execute([':id' => $id]);
    }

    public function list(int $limit = 50, int $offset = 0, ?string $from = null, ?string $to = null, ?string $q = null): array
    {
        $where = [];$params = [];
        if ($from) { $where[] = 'data >= :from'; $params[':from'] = $from; }
        if ($to) { $where[] = 'data <= :to'; $params[':to'] = $to; }
        if ($q) { $where[] = '(instituicao LIKE :q OR descricao LIKE :q)'; $params[':q'] = "%$q%"; }
        $sql = 'SELECT * FROM doacoes';
        if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
        $sql .= ' ORDER BY data DESC LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function totals(): array
    {
        $row = $this->db->query('SELECT COALESCE(SUM(valor_brl),0) as total FROM doacoes WHERE status="ativo"')->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['total_doacoes_brl' => (float)($row['total'] ?? 0)];
    }

    public function totalsPeriod(?string $from = null, ?string $to = null): array
    {
        $where = ['status = "ativo"'];
        $p = [];
        if ($from) { $where[] = 'data >= :from'; $p[':from'] = $from; }
        if ($to) { $where[] = 'data <= :to'; $p[':to'] = $to; }
        $sql = 'SELECT COALESCE(SUM(valor_brl),0) as total FROM doacoes';
        if ($where) { $sql .= ' WHERE '.implode(' AND ', $where); }
        $stmt = $this->db->prepare($sql);
        foreach ($p as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['total_doado_periodo_brl' => (float)($row['total'] ?? 0)];
    }
}

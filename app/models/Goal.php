<?php
namespace Models;

use Core\Model;
use PDO;

class Goal extends Model
{
    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare('INSERT INTO metas (titulo, descricao, tipo, valor_meta, moeda, data_inicio, data_fim, criado_por, criado_em)
            VALUES (:titulo, :descricao, :tipo, :valor_meta, :moeda, :data_inicio, :data_fim, :criado_por, NOW())');
        $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':tipo' => $data['tipo'],
            ':valor_meta' => (float)$data['valor_meta'],
            ':moeda' => $data['moeda'],
            ':data_inicio' => $data['data_inicio'],
            ':data_fim' => $data['data_fim'],
            ':criado_por' => $userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE metas SET titulo=:titulo, descricao=:descricao, tipo=:tipo, valor_meta=:valor_meta, moeda=:moeda, data_inicio=:data_inicio, data_fim=:data_fim WHERE id=:id');
        $stmt->execute([
            ':id' => $id,
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':tipo' => $data['tipo'],
            ':valor_meta' => (float)$data['valor_meta'],
            ':moeda' => $data['moeda'],
            ':data_inicio' => $data['data_inicio'],
            ':data_fim' => $data['data_fim'],
        ]);
    }

    public function deleteRow(int $id): void
    {
        $this->db->prepare('DELETE FROM metas WHERE id=:id')->execute([':id'=>$id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM metas WHERE id=:id');
        $stmt->execute([':id'=>$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function list(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT m.*, u.name as criador FROM metas m LEFT JOIN usuarios u ON u.id = m.criado_por ORDER BY m.data_inicio DESC, m.id DESC LIMIT :lim OFFSET :off');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Sales totals in USD for global period
    public function salesTotalUsd(string $from, string $to, ?int $sellerId = null): float
    {
        // vendas (created_at) USD totals
        $sqlV = 'SELECT COALESCE(SUM(bruto_usd),0) t FROM vendas WHERE created_at BETWEEN :f AND :t';
        if ($sellerId) $sqlV .= ' AND usuario_id = :sid';
        $stmt = $this->db->prepare($sqlV);
        $stmt->bindValue(':f', $from.' 00:00:00');
        $stmt->bindValue(':t', $to.' 23:59:59');
        if ($sellerId) $stmt->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stmt->execute();
        $v = (float)($stmt->fetchColumn() ?: 0);
        // vendas_internacionais (data_lancamento) USD totals
        $sqlI = 'SELECT COALESCE(SUM(total_bruto_usd),0) t FROM vendas_internacionais WHERE data_lancamento BETWEEN :f2 AND :t2';
        if ($sellerId) $sqlI .= ' AND vendedor_id = :sid2';
        $stmt2 = $this->db->prepare($sqlI);
        $stmt2->bindValue(':f2', $from);
        $stmt2->bindValue(':t2', $to);
        if ($sellerId) $stmt2->bindValue(':sid2', $sellerId, PDO::PARAM_INT);
        $stmt2->execute();
        $i = (float)($stmt2->fetchColumn() ?: 0);
        // vendas_nacionais (data_lancamento) USD totals
        $sqlN = 'SELECT COALESCE(SUM(total_bruto_usd),0) t FROM vendas_nacionais WHERE data_lancamento BETWEEN :f3 AND :t3';
        if ($sellerId) $sqlN .= ' AND vendedor_id = :sid3';
        $stmt3 = $this->db->prepare($sqlN);
        $stmt3->bindValue(':f3', $from);
        $stmt3->bindValue(':t3', $to);
        if ($sellerId) $stmt3->bindValue(':sid3', $sellerId, PDO::PARAM_INT);
        $stmt3->execute();
        $n = (float)($stmt3->fetchColumn() ?: 0);
        return $v + $i + $n;
    }
}

<?php
namespace Models;

use Core\Model;
use PDO;

class Cost extends Model
{
    public function create(string $date, string $category, ?string $desc, float $amountUsd): int
    {
        $stmt = $this->db->prepare('INSERT INTO custos (data, categoria, descricao, valor_usd, created_at) VALUES
            (:data, :categoria, :descricao, :valor_usd, NOW())');
        $stmt->execute([
            ':data' => $date,
            ':categoria' => $category,
            ':descricao' => $desc,
            ':valor_usd' => $amountUsd,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Create a cost with recurrence metadata (master row). */
    public function createFull(array $payload): int
    {
        $sql = 'INSERT INTO custos (
                    data, categoria, descricao,
                    valor_usd, valor_tipo, valor_brl, valor_percent,
                    recorrente_tipo, recorrente_ativo, recorrente_proxima_data,
                    parcelas_total, parcela_atual, created_at, updated_at
                ) VALUES (
                    :data, :categoria, :descricao,
                    :valor_usd, :valor_tipo, :valor_brl, :valor_percent,
                    :recorrente_tipo, :recorrente_ativo, :recorrente_proxima_data,
                    :parcelas_total, :parcela_atual, NOW(), NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':data' => $payload['data'],
            ':categoria' => $payload['categoria'],
            ':descricao' => $payload['descricao'] ?? null,
            ':valor_usd' => $payload['valor_usd'],
            ':valor_tipo' => $payload['valor_tipo'] ?? 'usd',
            ':valor_brl' => $payload['valor_brl'] ?? null,
            ':valor_percent' => $payload['valor_percent'] ?? null,
            ':recorrente_tipo' => $payload['recorrente_tipo'] ?? 'none',
            ':recorrente_ativo' => (int)($payload['recorrente_ativo'] ?? 0),
            ':recorrente_proxima_data' => $payload['recorrente_proxima_data'] ?? null,
            ':parcelas_total' => $payload['parcelas_total'] ?? null,
            ':parcela_atual' => $payload['parcela_atual'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $limit = 50, int $offset = 0, ?string $from = null, ?string $to = null): array
    {
        $sql = 'SELECT * FROM custos WHERE 1=1';
        $params = [];
        if ($from) { $sql .= ' AND data >= :from'; $params[':from'] = $from; }
        if ($to) { $sql .= ' AND data <= :to'; $params[':to'] = $to; }
        $sql .= ' ORDER BY data DESC, id DESC LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM custos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /** Update a cost entry core data. */
    public function updateFull(int $id, array $payload): void
    {
        $sql = 'UPDATE custos SET
                    data = :data,
                    categoria = :categoria,
                    descricao = :descricao,
                    valor_usd = :valor_usd,
                    valor_tipo = :valor_tipo,
                    valor_brl = :valor_brl,
                    valor_percent = :valor_percent,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':data' => $payload['data'],
            ':categoria' => $payload['categoria'],
            ':descricao' => $payload['descricao'] ?? null,
            ':valor_usd' => $payload['valor_usd'],
            ':valor_tipo' => $payload['valor_tipo'] ?? 'usd',
            ':valor_brl' => $payload['valor_brl'] ?? null,
            ':valor_percent' => $payload['valor_percent'] ?? null,
        ]);
    }

    /** Update master recurrence fields for a cost id. */
    public function updateRecurrence(int $id, array $fields): void
    {
        $sets = [];
        $params = [':id' => $id];
        foreach (['recorrente_tipo','recorrente_ativo','recorrente_proxima_data','parcelas_total','parcela_atual'] as $k) {
            if (array_key_exists($k, $fields)) { $sets[] = "$k = :$k"; $params[":$k"] = $fields[$k]; }
        }
        if (!$sets) return;
        $sql = 'UPDATE custos SET '.implode(', ',$sets).', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function sumBetween(string $from, string $to): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(valor_usd),0) as s FROM custos WHERE data BETWEEN :f AND :t');
        $stmt->execute([':f' => $from, ':t' => $to]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['s'] ?? 0);
    }

    /** Return overall sums grouped by tipo for API calculator. */
    public function globalSums(): array
    {
        $fixedUsd = (float)($this->db->query("SELECT COALESCE(SUM(valor_usd),0) s FROM custos WHERE valor_tipo='usd'")->fetch(PDO::FETCH_ASSOC)['s'] ?? 0);
        $fixedBrl = (float)($this->db->query("SELECT COALESCE(SUM(valor_brl),0) s FROM custos WHERE valor_tipo='brl'")->fetch(PDO::FETCH_ASSOC)['s'] ?? 0);
        $percent = (float)($this->db->query("SELECT COALESCE(SUM(valor_percent),0) s FROM custos WHERE valor_tipo='percent'")->fetch(PDO::FETCH_ASSOC)['s'] ?? 0);
        return ['fixed_usd'=>$fixedUsd,'fixed_brl'=>$fixedBrl,'percent'=>$percent];
    }
}

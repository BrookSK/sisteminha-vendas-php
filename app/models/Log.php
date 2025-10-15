<?php
namespace Models;

use Core\Model;

class Log extends Model
{
    public function add(?int $usuarioId, string $entidade, string $acao, ?int $refId = null, ?string $detalhes = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO logs (usuario_id, entidade, acao, ref_id, detalhes, created_at) VALUES
            (:usuario_id, :entidade, :acao, :ref_id, :detalhes, NOW())');
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':entidade' => $entidade,
            ':acao' => $acao,
            ':ref_id' => $refId,
            ':detalhes' => $detalhes,
        ]);
    }

    public function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT l.*, u.email as usuario_email FROM logs l
                LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE 1=1';
        $params = [];
        if (!empty($filters['entidade'])) { $sql .= ' AND l.entidade = :entidade'; $params[':entidade'] = $filters['entidade']; }
        if (!empty($filters['acao'])) { $sql .= ' AND l.acao = :acao'; $params[':acao'] = $filters['acao']; }
        if (!empty($filters['usuario_id'])) { $sql .= ' AND l.usuario_id = :usuario_id'; $params[':usuario_id'] = (int)$filters['usuario_id']; }
        if (!empty($filters['de'])) { $sql .= ' AND l.created_at >= :de'; $params[':de'] = $filters['de'] . ' 00:00:00'; }
        if (!empty($filters['ate'])) { $sql .= ' AND l.created_at <= :ate'; $params[':ate'] = $filters['ate'] . ' 23:59:59'; }
        $sql .= ' ORDER BY l.created_at DESC LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function count(array $filters): int
    {
        $sql = 'SELECT COUNT(*) as c FROM logs l WHERE 1=1';
        $params = [];
        if (!empty($filters['entidade'])) { $sql .= ' AND l.entidade = :entidade'; $params[':entidade'] = $filters['entidade']; }
        if (!empty($filters['acao'])) { $sql .= ' AND l.acao = :acao'; $params[':acao'] = $filters['acao']; }
        if (!empty($filters['usuario_id'])) { $sql .= ' AND l.usuario_id = :usuario_id'; $params[':usuario_id'] = (int)$filters['usuario_id']; }
        if (!empty($filters['de'])) { $sql .= ' AND l.created_at >= :de'; $params[':de'] = $filters['de'] . ' 00:00:00'; }
        if (!empty($filters['ate'])) { $sql .= ' AND l.created_at <= :ate'; $params[':ate'] = $filters['ate'] . ' 23:59:59'; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }
}

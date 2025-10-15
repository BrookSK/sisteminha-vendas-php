<?php
namespace Models;

use Core\Model;
use PDO;

class GoalAssignment extends Model
{
    public function upsert(int $goalId, int $sellerId, float $valorMeta): void
    {
        $stmt = $this->db->prepare('INSERT INTO metas_vendedores (id_meta, id_vendedor, valor_meta, progresso_atual, atualizado_em)
            VALUES (:m, :u, :v, 0, NOW())
            ON DUPLICATE KEY UPDATE valor_meta=VALUES(valor_meta), atualizado_em=NOW()');
        $stmt->execute([':m'=>$goalId, ':u'=>$sellerId, ':v'=>$valorMeta]);
    }

    public function listByGoal(int $goalId): array
    {
        $stmt = $this->db->prepare('SELECT mv.*, u.name, u.email FROM metas_vendedores mv LEFT JOIN usuarios u ON u.id=mv.id_vendedor WHERE mv.id_meta=:m ORDER BY u.name');
        $stmt->execute([':m'=>$goalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listForUser(int $userId, int $limit=50, int $offset=0): array
    {
        $stmt = $this->db->prepare('SELECT mv.*, m.titulo, m.tipo, m.data_inicio, m.data_fim, m.moeda FROM metas_vendedores mv
            LEFT JOIN metas m ON m.id=mv.id_meta WHERE mv.id_vendedor=:u ORDER BY m.data_inicio DESC LIMIT :l OFFSET :o');
        $stmt->bindValue(':u',$userId,PDO::PARAM_INT);
        $stmt->bindValue(':l',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':o',$offset,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateProgress(int $goalId, int $sellerId, float $progress): void
    {
        $stmt = $this->db->prepare('UPDATE metas_vendedores SET progresso_atual=:p, atualizado_em=NOW() WHERE id_meta=:m AND id_vendedor=:u');
        $stmt->execute([':p'=>$progress, ':m'=>$goalId, ':u'=>$sellerId]);
    }
}

<?php
namespace Models;

use Core\Model;
use PDO;

class Attendance extends Model
{
    public function upsert(string $date, int $total, int $done, ?int $userId = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO atendimentos (data, total_atendimentos, total_concluidos, usuario_id, created_at, updated_at)
            VALUES (:data, :total, :done, :uid, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_atendimentos = VALUES(total_atendimentos), total_concluidos = VALUES(total_concluidos), usuario_id = VALUES(usuario_id), updated_at = NOW()');
        $stmt->execute([
            ':data' => $date,
            ':total' => $total,
            ':done' => $done,
            ':uid' => $userId,
        ]);
    }

    public function list(int $limit = 30, int $offset = 0, ?int $userId = null): array
    {
        if ($userId) {
            $stmt = $this->db->prepare('SELECT a.*, u.email as usuario_email FROM atendimentos a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.usuario_id = :uid
                ORDER BY a.data DESC LIMIT :lim OFFSET :off');
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('SELECT a.*, u.email as usuario_email FROM atendimentos a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                ORDER BY a.data DESC LIMIT :lim OFFSET :off');
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(?int $userId = null): int
    {
        if ($userId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM atendimentos WHERE usuario_id = :uid');
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } else {
            $row = $this->db->query('SELECT COUNT(*) as c FROM atendimentos')->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        return (int)($row['c'] ?? 0);
    }
}

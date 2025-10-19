<?php
namespace Models;

use Core\Model;
use PDO;

class Approval extends Model
{
    public function createPending(string $entityType, string $action, array $payload, int $createdBy, ?int $reviewerId = null, ?int $entityId = null): int
    {
        $st = $this->db->prepare('INSERT INTO approvals (entity_type, entity_id, action, payload, status, created_by, reviewer_id, created_at) VALUES (:et, :eid, :act, :payload, "pending", :cb, :rv, NOW())');
        $st->execute([
            ':et' => $entityType,
            ':eid' => $entityId,
            ':act' => $action,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            ':cb' => $createdBy,
            ':rv' => $reviewerId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function approve(int $id, int $reviewerId): void
    {
        $st = $this->db->prepare('UPDATE approvals SET status="approved", reviewer_id = :rv, decided_at = NOW() WHERE id=:id AND status="pending"');
        $st->execute([':rv'=>$reviewerId, ':id'=>$id]);
    }

    public function reject(int $id, int $reviewerId): void
    {
        $st = $this->db->prepare('UPDATE approvals SET status="rejected", reviewer_id = :rv, decided_at = NOW() WHERE id=:id AND status="pending"');
        $st->execute([':rv'=>$reviewerId, ':id'=>$id]);
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM approvals WHERE id=:id');
        $st->execute([':id'=>$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listPendingForReviewer(int $reviewerId, int $limit = 50, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT * FROM approvals WHERE status="pending" AND (reviewer_id = :rv OR reviewer_id IS NULL) ORDER BY created_at DESC LIMIT :lim OFFSET :off');
        $st->bindValue(':rv', $reviewerId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

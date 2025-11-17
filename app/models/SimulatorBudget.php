<?php
namespace Models;

use Core\Model;
use PDO;

class SimulatorBudget extends Model
{
    public function createForUser(int $userId, string $name, array $payload): int
    {
        $st = $this->db->prepare('INSERT INTO simulator_budgets (user_id, name, data_json, created_at) VALUES (:uid, :name, :data, NOW())');
        $st->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':data' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateForUser(int $id, int $userId, string $name, array $payload): void
    {
        $st = $this->db->prepare('UPDATE simulator_budgets SET name=:name, data_json=:data, updated_at=NOW() WHERE id=:id AND user_id=:uid');
        $st->execute([
            ':id' => $id,
            ':uid' => $userId,
            ':name' => $name,
            ':data' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM simulator_budgets WHERE id=:id AND user_id=:uid');
        $st->execute([':id'=>$id, ':uid'=>$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listForUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT id, name, created_at, updated_at FROM simulator_budgets WHERE user_id=:uid ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off');
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countForUser(int $userId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) c FROM simulator_budgets WHERE user_id=:uid');
        $st->execute([':uid'=>$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['c'] ?? 0);
    }

    public function duplicateForUser(int $id, int $userId, ?string $newName = null): ?int
    {
        $orig = $this->findForUser($id, $userId);
        if (!$orig) return null;
        $name = $newName ?: ($orig['name'].' (cópia)');
        $payload = json_decode($orig['data_json'] ?? '[]', true) ?: [];
        return $this->createForUser($userId, $name, $payload);
    }

    public function deleteForUser(int $id, int $userId): void
    {
        $st = $this->db->prepare('DELETE FROM simulator_budgets WHERE id=:id AND user_id=:uid');
        $st->execute([':id'=>$id, ':uid'=>$userId]);
    }
}

<?php
namespace Models;

use Core\Model;
use PDO;

class Project extends Model
{
    public function list(int $limit = 200, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT * FROM projects ORDER BY start_date DESC, id DESC LIMIT :lim OFFSET :off');
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM projects WHERE id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d, ?int $userId): int
    {
        $st = $this->db->prepare('INSERT INTO projects (name,status,start_date,due_date,description,created_by,created_at) VALUES (:name,:status,:start_date,:due_date,:description,:cb,NOW())');
        $st->execute([
            ':name'=>$d['name'],
            ':status'=>$d['status'],
            ':start_date'=>$d['start_date'],
            ':due_date'=>$d['due_date'],
            ':description'=>$d['description'] ?? null,
            ':cb'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $d): void
    {
        $st = $this->db->prepare('UPDATE projects SET name=:name,status=:status,start_date=:start_date,due_date=:due_date,description=:description,updated_at=NOW() WHERE id=:id');
        $st->execute([
            ':id'=>$id,
            ':name'=>$d['name'],
            ':status'=>$d['status'],
            ':start_date'=>$d['start_date'],
            ':due_date'=>$d['due_date'],
            ':description'=>$d['description'] ?? null,
        ]);
    }

    public function relatedDemands(int $projectId): array
    {
        $sql = 'SELECT d.*, u.name as assignee_name FROM demands d LEFT JOIN usuarios u ON u.id = d.assignee_id WHERE d.project_id = :pid ORDER BY status ASC, priority DESC, due_date ASC';
        $st = $this->db->prepare($sql);
        $st->execute([':pid'=>$projectId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

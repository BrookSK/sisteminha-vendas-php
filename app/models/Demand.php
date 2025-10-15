<?php
namespace Models;

use Core\Model;
use PDO;

class Demand extends Model
{
    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM demands WHERE id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d, ?int $userId): int
    {
        $st = $this->db->prepare('INSERT INTO demands (title,type_desc,assignee_id,project_id,status,due_date,priority,classification,details,created_by,created_at)
            VALUES (:title,:type_desc,:assignee_id,:project_id,:status,:due_date,:priority,:classification,:details,:cb,NOW())');
        $st->execute([
            ':title'=>$d['title'],
            ':type_desc'=>$d['type_desc'],
            ':assignee_id'=>$d['assignee_id'] ?? null,
            ':project_id'=>$d['project_id'] ?? null,
            ':status'=>$d['status'] ?? 'pendente',
            ':due_date'=>$d['due_date'] ?? null,
            ':priority'=>$d['priority'] ?? 'baixa',
            ':classification'=>$d['classification'] ?? null,
            ':details'=>$d['details'] ?? null,
            ':cb'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $d): void
    {
        $st = $this->db->prepare('UPDATE demands SET title=:title,type_desc=:type_desc,assignee_id=:assignee_id,project_id=:project_id,status=:status,due_date=:due_date,priority=:priority,classification=:classification,details=:details,updated_at=NOW() WHERE id=:id');
        $st->execute([
            ':id'=>$id,
            ':title'=>$d['title'],
            ':type_desc'=>$d['type_desc'],
            ':assignee_id'=>$d['assignee_id'] ?? null,
            ':project_id'=>$d['project_id'] ?? null,
            ':status'=>$d['status'] ?? 'pendente',
            ':due_date'=>$d['due_date'] ?? null,
            ':priority'=>$d['priority'] ?? 'baixa',
            ':classification'=>$d['classification'] ?? null,
            ':details'=>$d['details'] ?? null,
        ]);
    }

    public function listAll(?string $tab = 'pendentes', ?int $userId = null, ?bool $restrictToOwn = false): array
    {
        // tabs: pendentes (status != entregue/arquivado), entregues (status = entregue)
        $where = [];$p=[];
        if ($tab === 'entregues') {
            $where[] = "d.status = 'entregue'";
        } else {
            $where[] = "d.status NOT IN ('entregue','arquivado')";
        }
        if ($restrictToOwn && $userId) {
            $where[] = 'd.created_by = :uid';
            $p[':uid'] = $userId;
        }
        $sql = 'SELECT d.*, u.name as assignee_name, p.name as project_name FROM demands d
                LEFT JOIN usuarios u ON u.id = d.assignee_id
                LEFT JOIN projects p ON p.id = d.project_id';
        if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
        $sql .= ' ORDER BY COALESCE(d.due_date, "9999-12-31") ASC, d.priority DESC, d.id DESC LIMIT 300';
        $st = $this->db->prepare($sql);
        foreach ($p as $k=>$v) { $st->bindValue($k,$v); }
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function backlog(): array
    {
        $sql = "SELECT d.*, p.name as project_name FROM demands d LEFT JOIN projects p ON p.id=d.project_id
                WHERE d.status='pendente' AND d.assignee_id IS NULL ORDER BY COALESCE(d.due_date, '9999-12-31') ASC, d.priority DESC, d.id DESC LIMIT 100";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function dueToday(): array
    {
        $sql = "SELECT d.*, u.name as assignee_name FROM demands d LEFT JOIN usuarios u ON u.id=d.assignee_id
                WHERE d.due_date = CURDATE() ORDER BY d.priority DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function late(): array
    {
        $sql = "SELECT d.*, u.name as assignee_name FROM demands d LEFT JOIN usuarios u ON u.id=d.assignee_id
                WHERE d.due_date < CURDATE() AND d.status NOT IN ('entregue','arquivado') ORDER BY d.due_date ASC, d.priority DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function weekSchedule(): array
    {
        // Return map by date => demands[] for current week (Sun..Sat)
        $sun = date('Y-m-d', strtotime('sunday last week'));
        $sat = date('Y-m-d', strtotime('saturday this week'));
        $st = $this->db->prepare("SELECT d.*, u.name as assignee_name FROM demands d LEFT JOIN usuarios u ON u.id=d.assignee_id WHERE d.due_date BETWEEN :s AND :e ORDER BY d.due_date ASC, d.priority DESC");
        $st->execute([':s'=>$sun, ':e'=>$sat]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) { $d = $r['due_date']; if (!$d) continue; $out[$d][] = $r; }
        return $out;
    }
}

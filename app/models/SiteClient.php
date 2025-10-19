<?php
namespace Models;

use Core\Model;
use PDO;

class SiteClient extends Model
{
    public function list(int $limit = 500, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT * FROM clients ORDER BY name ASC LIMIT :lim OFFSET :off');
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM clients WHERE id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d): int
    {
        $st = $this->db->prepare('INSERT INTO clients (name,email,phone,created_at) VALUES (:name,:email,:phone,NOW())');
        $st->execute([
            ':name'=>trim($d['name'] ?? ''),
            ':email'=>trim($d['email'] ?? ''),
            ':phone'=>trim($d['phone'] ?? ''),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $d): void
    {
        $st = $this->db->prepare('UPDATE clients SET name=:name,email=:email,phone=:phone,updated_at=NOW() WHERE id=:id');
        $st->execute([
            ':id'=>$id,
            ':name'=>trim($d['name'] ?? ''),
            ':email'=>trim($d['email'] ?? ''),
            ':phone'=>trim($d['phone'] ?? ''),
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM clients WHERE id=:id');
        $st->execute([':id'=>$id]);
    }

    public function searchLite(?string $q = null, int $limit = 20, int $offset = 0): array
    {
        $conds = [];$vals = [];
        if ($q) {
            $like = "%$q%";
            $conds[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $vals[] = $like; $vals[] = $like; $vals[] = $like;
        }
        $sql = 'SELECT id,name,email,phone FROM clients';
        if ($conds) { $sql .= ' WHERE '.implode(' AND ', $conds); }
        $sql .= ' ORDER BY name ASC LIMIT '.(int)$limit.' OFFSET '.(int)$offset;
        $st = $this->db->prepare($sql);
        $st->execute($vals);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

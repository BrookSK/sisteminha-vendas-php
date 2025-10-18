<?php
namespace Models;

use Core\Model;
use PDO;

class DocumentationArea extends Model
{
    public function listAll(): array
    {
        $st = $this->db->query('SELECT * FROM documentation_areas ORDER BY name ASC');
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(string $name): int
    {
        $st = $this->db->prepare('INSERT INTO documentation_areas (name, created_at) VALUES (:n, NOW())');
        $st->execute([':n'=>$name]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $st = $this->db->prepare('UPDATE documentation_areas SET name=:n WHERE id=:id');
        $st->execute([':id'=>$id, ':n'=>$name]);
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM documentation_areas WHERE id=:id');
        $st->execute([':id'=>$id]);
    }

    public function inUse(int $id): bool
    {
        $st = $this->db->prepare('SELECT COUNT(1) FROM documentations WHERE area_id = :id');
        $st->execute([':id'=>$id]);
        return (int)$st->fetchColumn() > 0;
    }
}

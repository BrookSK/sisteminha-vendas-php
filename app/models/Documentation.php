<?php
namespace Models;

use Core\Model;
use PDO;

class Documentation extends Model
{
    public function list(int $limit = 200, int $offset = 0): array
    {
        // MySQL-compatible: order by updated_at (nulls last) then created_at desc
        $sql = 'SELECT d.*, a.name as area_name FROM documentations d LEFT JOIN documentation_areas a ON a.id = d.area_id ORDER BY (d.updated_at IS NULL), d.updated_at DESC, d.created_at DESC LIMIT :lim OFFSET :off';
        $st = $this->db->prepare($sql);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countFilteredByVisibility(array $allowed, array $filters): int
    {
        if (empty($allowed)) return 0;
        $wheres = [];
        $params = [];
        $wheres[] = 'd.internal_visibility IN (' . implode(',', array_fill(0, count($allowed), '?')) . ')';
        foreach ($allowed as $v) { $params[] = $v; }
        if (!empty($filters['status'])) { $wheres[] = 'd.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['area_id'])) { $wheres[] = 'd.area_id = ?'; $params[] = (int)$filters['area_id']; }
        if (!empty($filters['project_id'])) { $wheres[] = 'd.project_id = ?'; $params[] = (int)$filters['project_id']; }
        $sql = 'SELECT COUNT(1) FROM documentations d';
        if (!empty($wheres)) { $sql .= ' WHERE ' . implode(' AND ', $wheres); }
        $st = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $type = is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $st->bindValue($i++, $p, $type);
        }
        $st->execute();
        return (int)$st->fetchColumn();
    }

    public function listByVisibility(array $allowed, int $limit = 200, int $offset = 0): array
    {
        if (empty($allowed)) return [];
        $in = implode(',', array_fill(0, count($allowed), '?'));
        $sql = 'SELECT d.*, a.name as area_name FROM documentations d LEFT JOIN documentation_areas a ON a.id = d.area_id WHERE d.internal_visibility IN (' . $in . ') ORDER BY (d.updated_at IS NULL), d.updated_at DESC, d.created_at DESC LIMIT ? OFFSET ?';
        $st = $this->db->prepare($sql);
        $i = 1;
        foreach ($allowed as $v) { $st->bindValue($i++, $v); }
        $st->bindValue($i++, $limit, PDO::PARAM_INT);
        $st->bindValue($i++, $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listFilteredByVisibility(array $allowed, array $filters, int $limit = 200, int $offset = 0): array
    {
        if (empty($allowed)) return [];
        $wheres = [];
        $params = [];
        // visibility
        $wheres[] = 'd.internal_visibility IN (' . implode(',', array_fill(0, count($allowed), '?')) . ')';
        foreach ($allowed as $v) { $params[] = $v; }
        // status
        if (!empty($filters['status'])) { $wheres[] = 'd.status = ?'; $params[] = $filters['status']; }
        // area
        if (!empty($filters['area_id'])) { $wheres[] = 'd.area_id = ?'; $params[] = (int)$filters['area_id']; }
        // project
        if (!empty($filters['project_id'])) { $wheres[] = 'd.project_id = ?'; $params[] = (int)$filters['project_id']; }

        $sql = 'SELECT d.*, a.name as area_name FROM documentations d LEFT JOIN documentation_areas a ON a.id = d.area_id';
        if (!empty($wheres)) { $sql .= ' WHERE ' . implode(' AND ', $wheres); }
        $sql .= ' ORDER BY (d.updated_at IS NULL), d.updated_at DESC, d.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit; $params[] = $offset;

        $st = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $type = is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $st->bindValue($i++, $p, $type);
        }
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM documentations WHERE id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = $this->db->prepare('SELECT * FROM documentations WHERE external_slug=:s');
        $st->execute([':s'=>$slug]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d, ?int $userId): int
    {
        $st = $this->db->prepare('INSERT INTO documentations (title,status,project_id,area_id,internal_visibility,published,external_slug,content,created_by,created_at) VALUES (:title,:status,:project_id,:area_id,:vis,0,NULL,:content,:cb,NOW())');
        $st->execute([
            ':title'=>$d['title'],
            ':status'=>$d['status'],
            ':project_id'=>$d['project_id'] ?? null,
            ':area_id'=>$d['area_id'] ?? null,
            ':vis'=>$d['internal_visibility'],
            ':content'=>$d['content'] ?? null,
            ':cb'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $d, ?int $userId = null): void
    {
        $st = $this->db->prepare('UPDATE documentations SET title=:title,status=:status,project_id=:project_id,area_id=:area_id,internal_visibility=:vis,content=:content,updated_by=:ub,updated_at=NOW() WHERE id=:id');
        $st->execute([
            ':id'=>$id,
            ':title'=>$d['title'],
            ':status'=>$d['status'],
            ':project_id'=>$d['project_id'] ?? null,
            ':area_id'=>$d['area_id'] ?? null,
            ':vis'=>$d['internal_visibility'],
            ':content'=>$d['content'] ?? null,
            ':ub'=>$userId,
        ]);
    }

    public function setPublished(int $id, bool $published): void
    {
        $st = $this->db->prepare('UPDATE documentations SET published=:p, updated_at=NOW() WHERE id=:id');
        $st->execute([':id'=>$id, ':p'=>$published ? 1 : 0]);
    }

    public function setSlug(int $id, ?string $slug): void
    {
        $st = $this->db->prepare('UPDATE documentations SET external_slug=:s, updated_at=NOW() WHERE id=:id');
        $st->execute([':id'=>$id, ':s'=>$slug]);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $st = $this->db->prepare('SELECT 1 FROM documentations WHERE external_slug=:s AND id<>:id');
            $st->execute([':s'=>$slug, ':id'=>$excludeId]);
        } else {
            $st = $this->db->prepare('SELECT 1 FROM documentations WHERE external_slug=:s');
            $st->execute([':s'=>$slug]);
        }
        return (bool)$st->fetchColumn();
    }
}

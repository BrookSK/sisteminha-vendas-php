<?php
namespace Models;

use Core\Model;
use PDO;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        if ($email === '') return null;
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('INSERT INTO usuarios (name, email, password_hash, created_at) VALUES (:name, :email, :hash, NOW())');
        $stmt->execute([':name' => $name, ':email' => $email, ':hash' => $hash]);
        return (int)$this->db->lastInsertId();
    }

    public function createWithRole(string $name, string $email, string $password, string $role, int $ativo = 1): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('INSERT INTO usuarios (name, email, password_hash, role, ativo, created_at) VALUES (:name, :email, :hash, :role, :ativo, NOW())');
        $stmt->execute([':name' => $name, ':email' => $email, ':hash' => $hash, ':role' => $role, ':ativo' => $ativo]);
        return (int)$this->db->lastInsertId();
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as c FROM usuarios');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function allBasic(): array
    {
        $stmt = $this->db->query('SELECT id, name, email, role, ativo FROM usuarios ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function paginate(int $limit, int $offset, ?string $q = null): array
    {
        if ($q) {
            $stmt = $this->db->prepare('SELECT id, name, email, role, ativo FROM usuarios WHERE name LIKE :q1 OR email LIKE :q2 ORDER BY name LIMIT :limit OFFSET :offset');
            $like = "%$q%";
            $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('SELECT id, name, email, role, ativo FROM usuarios ORDER BY name LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countFiltered(?string $q = null): int
    {
        if ($q) {
            $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM usuarios WHERE name LIKE :q1 OR email LIKE :q2');
            $like = "%$q%";
            $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) as c FROM usuarios');
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function updateUser(int $id, string $name, string $email, ?string $password, string $role, int $ativo): void
    {
        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare('UPDATE usuarios SET name=:name, email=:email, password_hash=:hash, role=:role, ativo=:ativo WHERE id=:id');
            $stmt->execute([':name'=>$name, ':email'=>$email, ':hash'=>$hash, ':role'=>$role, ':ativo'=>$ativo, ':id'=>$id]);
        } else {
            $stmt = $this->db->prepare('UPDATE usuarios SET name=:name, email=:email, role=:role, ativo=:ativo WHERE id=:id');
            $stmt->execute([':name'=>$name, ':email'=>$email, ':role'=>$role, ':ativo'=>$ativo, ':id'=>$id]);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET name=:name, email=:email WHERE id=:id');
        $stmt->execute([':name'=>$name, ':email'=>$email, ':id'=>$id]);
    }

    public function verifyPassword(int $id, string $currentPassword): bool
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM usuarios WHERE id=:id');
        $stmt->execute([':id'=>$id]);
        $hash = $stmt->fetchColumn();
        if (!$hash) return false;
        return password_verify($currentPassword, (string)$hash);
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE usuarios SET password_hash=:hash WHERE id=:id');
        $stmt->execute([':hash'=>$hash, ':id'=>$id]);
    }
}

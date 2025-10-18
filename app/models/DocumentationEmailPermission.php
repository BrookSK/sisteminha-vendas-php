<?php
namespace Models;

use Core\Model;
use PDO;

class DocumentationEmailPermission extends Model
{
    public function isAllowed(int $docId, string $email): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM documentation_email_permissions WHERE documentation_id=:d AND email=:e');
        $st->execute([':d'=>$docId, ':e'=>mb_strtolower(trim($email))]);
        return (bool)$st->fetchColumn();
    }

    public function listByDoc(int $docId): array
    {
        $st = $this->db->prepare('SELECT * FROM documentation_email_permissions WHERE documentation_id=:d ORDER BY email ASC');
        $st->execute([':d'=>$docId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function add(int $docId, string $email): void
    {
        $st = $this->db->prepare('INSERT IGNORE INTO documentation_email_permissions (documentation_id,email,created_at) VALUES (:d,:e,NOW())');
        $st->execute([':d'=>$docId, ':e'=>mb_strtolower(trim($email))]);
    }

    public function remove(int $docId, string $email): void
    {
        $st = $this->db->prepare('DELETE FROM documentation_email_permissions WHERE documentation_id=:d AND email=:e');
        $st->execute([':d'=>$docId, ':e'=>mb_strtolower(trim($email))]);
    }
}

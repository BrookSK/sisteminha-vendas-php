<?php
namespace Models;

use Core\Model;
use PDO;

class DocumentationComment extends Model
{
    public function listByDoc(int $docId, int $limit = 200, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT c.*, u.name as user_name FROM documentation_comments c LEFT JOIN usuarios u ON u.id = c.user_id WHERE c.documentation_id=:d ORDER BY c.id DESC LIMIT :lim OFFSET :off');
        $st->bindValue(':d', $docId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function add(int $docId, ?int $userId, string $content): int
    {
        $st = $this->db->prepare('INSERT INTO documentation_comments (documentation_id,user_id,content,created_at) VALUES (:d,:u,:c,NOW())');
        $st->execute([':d'=>$docId, ':u'=>$userId, ':c'=>$content]);
        return (int)$this->db->lastInsertId();
    }
}

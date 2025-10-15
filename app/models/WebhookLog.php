<?php
namespace Models;

use Core\Model;
use PDO;

class WebhookLog extends Model
{
    public function create(string $tipo, string $status, ?string $mensagem, array $payload): int
    {
        $stmt = $this->db->prepare('INSERT INTO webhook_logs (tipo, status, mensagem, payload_json, created_at) VALUES (:t,:s,:m,:p,NOW())');
        $stmt->execute([
            ':t' => $tipo,
            ':s' => $status,
            ':m' => $mensagem,
            ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM webhook_logs ORDER BY id DESC LIMIT :lim OFFSET :off');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM webhook_logs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

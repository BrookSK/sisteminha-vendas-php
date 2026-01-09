<?php
namespace Models;

use Core\Database;
use PDO;

class SimulatorWebhookProduct
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::pdoProducts();
    }

    public function upsert(array $data): void
    {
        $sql = 'INSERT INTO simulator_webhook_products (external_id, nome, image_url, store_id, store_name, qtd, peso_kg, valor_usd, links_json, event_date, created_at, updated_at)
                VALUES (:external_id, :nome, :image_url, :store_id, :store_name, :qtd, :peso_kg, :valor_usd, :links_json, :event_date, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  nome = VALUES(nome),
                  image_url = VALUES(image_url),
                  store_id = VALUES(store_id),
                  store_name = VALUES(store_name),
                  qtd = VALUES(qtd),
                  peso_kg = VALUES(peso_kg),
                  valor_usd = VALUES(valor_usd),
                  links_json = VALUES(links_json),
                  event_date = VALUES(event_date),
                  updated_at = NOW()';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':external_id' => (string)($data['external_id'] ?? ''),
            ':nome' => (string)($data['nome'] ?? ''),
            ':image_url' => $data['image_url'] ?? null,
            ':store_id' => isset($data['store_id']) && $data['store_id'] ? (int)$data['store_id'] : null,
            ':store_name' => $data['store_name'] ?? null,
            ':qtd' => (int)($data['qtd'] ?? 0),
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':valor_usd' => (float)($data['valor_usd'] ?? 0),
            ':links_json' => $data['links_json'] ?? null,
            ':event_date' => $data['event_date'] ?? null,
        ]);
    }

    public function listInRange(string $fromDate, string $toDate): array
    {
        $sql = 'SELECT external_id, nome, image_url, store_id, store_name, qtd, peso_kg, valor_usd, links_json, event_date
                FROM simulator_webhook_products';
        $params = [];
        if ($fromDate !== '' && $toDate !== '') {
            $sql .= ' WHERE event_date BETWEEN :from AND :to';
            $params[':from'] = $fromDate;
            $params[':to'] = $toDate;
        } elseif ($fromDate !== '') {
            $sql .= ' WHERE event_date >= :from';
            $params[':from'] = $fromDate;
        } elseif ($toDate !== '') {
            $sql .= ' WHERE event_date <= :to';
            $params[':to'] = $toDate;
        }
        $sql .= ' ORDER BY event_date ASC, id ASC';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

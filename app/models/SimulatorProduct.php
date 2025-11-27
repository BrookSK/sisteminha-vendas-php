<?php
namespace Models;

use Core\Database;
use PDO;

class SimulatorProduct
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::pdoProducts();
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    public function searchByName(?string $q = null, int $limit = 20, int $offset = 0): array
    {
        // Importante: não usar parâmetros nomeados em LIMIT/OFFSET para evitar SQLSTATE[HY093]
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $sql = 'SELECT id, sku, nome, marca, image_url, peso_kg, created_at, updated_at FROM simulator_products';
        $params = [];
        if ($q !== null && $q !== '') {
            $sql .= ' WHERE nome LIKE :q OR sku LIKE :q';
            $params[':q'] = '%'.$q.'%';
        }
        $sql .= ' ORDER BY nome ASC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, sku, nome, marca, image_url, peso_kg, created_at, updated_at FROM simulator_products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $linksStmt = $this->db->prepare('SELECT id, url, fonte FROM simulator_product_links WHERE product_id = :pid ORDER BY id ASC');
        $linksStmt->execute([':pid' => $id]);
        $row['links'] = $linksStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $row;
    }

    public function create(array $data, array $links = []): int
    {
        $stmt = $this->db->prepare('INSERT INTO simulator_products (sku, nome, marca, image_url, peso_kg, created_at) VALUES (:sku, :nome, :marca, :image_url, :peso_kg, NOW())');
        $stmt->execute([
            ':sku' => $data['sku'] ?? null,
            ':nome' => $data['nome'],
            ':marca' => $data['marca'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
        ]);
        $id = (int)$this->db->lastInsertId();

        if ($id > 0 && $links) {
            $linkStmt = $this->db->prepare('INSERT INTO simulator_product_links (product_id, url, fonte, created_at) VALUES (:pid, :url, :fonte, NOW())');
            foreach ($links as $lnk) {
                $url = trim($lnk['url'] ?? '');
                if ($url === '') continue;
                $linkStmt->execute([
                    ':pid' => $id,
                    ':url' => $url,
                    ':fonte' => $lnk['fonte'] ?? null,
                ]);
            }
        }

        return $id;
    }

    public function update(int $id, array $data, array $links = []): void
    {
        $stmt = $this->db->prepare('UPDATE simulator_products SET sku=:sku, nome=:nome, marca=:marca, image_url=:image_url, peso_kg=:peso_kg, updated_at=NOW() WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':sku' => $data['sku'] ?? null,
            ':nome' => $data['nome'],
            ':marca' => $data['marca'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
        ]);

        // Atualiza links (estratégia simples: remove e recria)
        $del = $this->db->prepare('DELETE FROM simulator_product_links WHERE product_id = :pid');
        $del->execute([':pid' => $id]);

        if ($links) {
            $ins = $this->db->prepare('INSERT INTO simulator_product_links (product_id, url, fonte, created_at) VALUES (:pid, :url, :fonte, NOW())');
            foreach ($links as $lnk) {
                $url = trim($lnk['url'] ?? '');
                if ($url === '') continue;
                $ins->execute([
                    ':pid' => $id,
                    ':url' => $url,
                    ':fonte' => $lnk['fonte'] ?? null,
                ]);
            }
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM simulator_product_links WHERE product_id = :pid');
        $stmt->execute([':pid' => $id]);
        $stmt = $this->db->prepare('DELETE FROM simulator_products WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}

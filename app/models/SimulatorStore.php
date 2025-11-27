<?php
namespace Models;

use Core\Database;
use PDO;

class SimulatorStore
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::pdoProducts();
    }

    public function all(): array
    {
        $st = $this->db->prepare('SELECT id, name FROM simulator_stores ORDER BY name ASC');
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(string $name): int
    {
        $name = trim($name);
        if ($name === '') return 0;
        $st = $this->db->prepare('INSERT INTO simulator_stores (name, created_at) VALUES (:name, NOW())');
        $st->execute([':name' => $name]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM simulator_stores WHERE id = :id');
        $st->execute([':id' => $id]);
    }
}

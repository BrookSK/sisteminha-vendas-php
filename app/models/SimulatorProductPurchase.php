<?php
namespace Models;

use Core\Database;
use PDO;

class SimulatorProductPurchase
{
    private PDO $db;

    public function __construct()
    {
        // Usa o mesmo banco de produtos que o SimulatorProduct (sisteminha_produtos_dev)
        $this->db = Database::pdoProducts();
    }
    public function getForKeys(array $keys): array
    {
        if (empty($keys)) return [];
        $in = implode(',', array_fill(0, count($keys), '?'));
        $sql = 'SELECT product_key, purchased_qtd FROM simulator_product_purchases WHERE product_key IN ('.$in.')';
        $st = $this->db->prepare($sql);
        foreach ($keys as $i => $k) {
            $st->bindValue($i+1, (string)$k, PDO::PARAM_STR);
        }
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[(string)$r['product_key']] = (int)$r['purchased_qtd'];
        }
        return $out;
    }

    public function setPurchasedQty(string $key, int $qty): void
    {
        $qty = max(0, $qty);
        // upsert simples
        $sql = 'INSERT INTO simulator_product_purchases (product_key, purchased_qtd, created_at, updated_at)
                VALUES (:k, :q, NOW(), NOW())
                ON DUPLICATE KEY UPDATE purchased_qtd = VALUES(purchased_qtd), updated_at = NOW()';
        $st = $this->db->prepare($sql);
        $st->execute([':k' => $key, ':q' => $qty]);
    }
}

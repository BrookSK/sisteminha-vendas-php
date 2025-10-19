<?php
namespace Models;

use Core\Model;
use PDO;

class AppSettings extends Model
{
    public function get(string $key, $default = null)
    {
        $st = $this->db->prepare('SELECT `value` FROM app_settings WHERE `key` = :k');
        $st->execute([':k'=>$key]);
        $v = $st->fetchColumn();
        return ($v === false) ? $default : $v;
    }

    public function set(string $key, ?string $value): void
    {
        $st = $this->db->prepare('INSERT INTO app_settings (`key`,`value`,updated_at) VALUES (:k,:v,NOW()) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), updated_at=NOW()');
        $st->execute([':k'=>$key, ':v'=>$value]);
    }

    public function all(array $keys): array
    {
        if (empty($keys)) return [];
        $in = implode(',', array_fill(0, count($keys), '?'));
        $st = $this->db->prepare('SELECT `key`,`value` FROM app_settings WHERE `key` IN ('.$in.')');
        $st->execute($keys);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) { $map[$r['key']] = $r['value']; }
        return $map;
    }
}

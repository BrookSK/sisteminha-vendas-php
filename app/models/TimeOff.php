<?php
namespace Models;

use Core\Model;
use PDO;

class TimeOff extends Model
{
    public function today(): array
    {
        $st = $this->db->prepare('SELECT t.*, u.name as user_name FROM time_off t LEFT JOIN usuarios u ON u.id=t.user_id WHERE t.date = CURDATE() ORDER BY u.name');
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(int $userId, string $date, string $reason): void
    {
        $st = $this->db->prepare('INSERT INTO time_off (user_id, date, reason, created_at) VALUES (:u, :d, :r, NOW())');
        $st->execute([':u'=>$userId, ':d'=>$date, ':r'=>$reason]);
    }
}

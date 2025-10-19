<?php
namespace Models;

use Core\Model;
use PDO;

class Hosting extends Model
{
    public function list(int $limit = 500, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT * FROM hostings ORDER BY status ASC, server_name ASC LIMIT :lim OFFSET :off');
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM hostings WHERE id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d, ?int $userId = null): int
    {
        $st = $this->db->prepare('INSERT INTO hostings (provider,server_name,plan_name,price,due_date,due_day,billing_cycle,server_ip,auto_payment,login_email,payer_responsible,status,description,created_by) VALUES (:provider,:server_name,:plan_name,:price,:due_date,:due_day,:billing_cycle,:server_ip,:auto_payment,:login_email,:payer_responsible,:status,:description,:cb)');
        $st->execute([
            ':provider'=>$d['provider'],
            ':server_name'=>$d['server_name'],
            ':plan_name'=>$d['plan_name'] ?? null,
            ':price'=>$d['price'] ?? null,
            ':due_date'=>$d['due_date'] ?? null,
            ':due_day'=>$d['due_day'] ?? null,
            ':billing_cycle'=>$d['billing_cycle'] ?? 'mensal',
            ':server_ip'=>$d['server_ip'] ?? null,
            ':auto_payment'=>!empty($d['auto_payment']) ? 1 : 0,
            ':login_email'=>$d['login_email'] ?? null,
            ':payer_responsible'=>$d['payer_responsible'] ?? null,
            ':status'=>$d['status'] ?? 'ativo',
            ':description'=>$d['description'] ?? null,
            ':cb'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $d, ?int $userId = null): void
    {
        $st = $this->db->prepare('UPDATE hostings SET provider=:provider,server_name=:server_name,plan_name=:plan_name,price=:price,due_date=:due_date,due_day=:due_day,billing_cycle=:billing_cycle,server_ip=:server_ip,auto_payment=:auto_payment,login_email=:login_email,payer_responsible=:payer_responsible,status=:status,description=:description,updated_by=:ub,updated_at=NOW() WHERE id=:id');
        $st->execute([
            ':id'=>$id,
            ':provider'=>$d['provider'],
            ':server_name'=>$d['server_name'],
            ':plan_name'=>$d['plan_name'] ?? null,
            ':price'=>$d['price'] ?? null,
            ':due_date'=>$d['due_date'] ?? null,
            ':due_day'=>$d['due_day'] ?? null,
            ':billing_cycle'=>$d['billing_cycle'] ?? 'mensal',
            ':server_ip'=>$d['server_ip'] ?? null,
            ':auto_payment'=>!empty($d['auto_payment']) ? 1 : 0,
            ':login_email'=>$d['login_email'] ?? null,
            ':payer_responsible'=>$d['payer_responsible'] ?? null,
            ':status'=>$d['status'] ?? 'ativo',
            ':description'=>$d['description'] ?? null,
            ':ub'=>$userId,
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM hostings WHERE id=:id');
        $st->execute([':id'=>$id]);
    }
}

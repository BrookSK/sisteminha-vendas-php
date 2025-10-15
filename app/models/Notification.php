<?php
namespace Models;

use Core\Model;
use PDO;

class Notification extends Model
{
    public function createWithTargets(int $createdBy, string $title, string $message, string $type, string $status, array $targets): int
    {
        $stmt = $this->db->prepare('INSERT INTO notifications (title, message, type, status, created_by, created_at) VALUES (:t,:m,:tp,:st,:cb, NOW())');
        $stmt->execute([':t'=>$title, ':m'=>$message, ':tp'=>$type, ':st'=>$status, ':cb'=>$createdBy]);
        $nid = (int)$this->db->lastInsertId();
        // Resolve users by target roles
        $roles = array_values(array_unique(array_map('strval', $targets)));
        if ($roles) {
            $in = str_repeat('?,', count($roles)-1) . '?';
            $q = $this->db->prepare("SELECT id, role FROM usuarios WHERE role IN ($in)");
            $q->execute($roles);
            $users = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $ins = $this->db->prepare('INSERT INTO notification_recipients (notification_id, user_id, role) VALUES (:n,:u,:r)');
            foreach ($users as $u) {
                $ins->execute([':n'=>$nid, ':u'=>$u['id'], ':r'=>$u['role']]);
            }
        }
        return $nid;
    }

    public function createWithUsers(int $createdBy, string $title, string $message, string $type, string $status, array $userIds): int
    {
        $stmt = $this->db->prepare('INSERT INTO notifications (title, message, type, status, created_by, created_at) VALUES (:t,:m,:tp,:st,:cb, NOW())');
        $stmt->execute([':t'=>$title, ':m'=>$message, ':tp'=>$type, ':st'=>$status, ':cb'=>$createdBy]);
        $nid = (int)$this->db->lastInsertId();
        if (!empty($userIds)) {
            $ins = $this->db->prepare('INSERT INTO notification_recipients (notification_id, user_id) VALUES (:n,:u)');
            $uniq = array_values(array_unique(array_map('intval', $userIds)));
            foreach ($uniq as $uid) {
                if ($uid > 0) { $ins->execute([':n'=>$nid, ':u'=>$uid]); }
            }
        }
        return $nid;
    }

    public function listForUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT nr.id as rid, n.*,
                       (nr.read_at IS NOT NULL) as lida,
                       (nr.archived_at IS NOT NULL) as arquivada
                FROM notification_recipients nr
                JOIN notifications n ON n.id = nr.notification_id
                WHERE nr.user_id = :u AND nr.deleted_at IS NULL
                ORDER BY n.created_at DESC
                LIMIT :lim OFFSET :off';
        $st = $this->db->prepare($sql);
        $st->bindValue(':u', $userId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function unreadCount(int $userId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) c FROM notification_recipients WHERE user_id=:u AND read_at IS NULL AND deleted_at IS NULL');
        $st->execute([':u'=>$userId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function listForUserFiltered(int $userId, array $filters, int $limit = 20, int $offset = 0): array
    {
        $where = ['nr.user_id = :u', 'nr.deleted_at IS NULL'];
        $params = [':u' => $userId];
        if (!empty($filters['type'])) { $where[] = 'n.type = :type'; $params[':type'] = $filters['type']; }
        if (!empty($filters['status'])) { $where[] = 'n.status = :status'; $params[':status'] = $filters['status']; }
        if (!empty($filters['from'])) { $where[] = 'n.created_at >= :from'; $params[':from'] = $filters['from'].' 00:00:00'; }
        if (!empty($filters['to'])) { $where[] = 'n.created_at <= :to'; $params[':to'] = $filters['to'].' 23:59:59'; }
        if (!empty($filters['created_by'])) { $where[] = 'n.created_by = :cb'; $params[':cb'] = (int)$filters['created_by']; }
        if (isset($filters['arch'])) {
            if ((string)$filters['arch'] === '1') { $where[] = 'nr.archived_at IS NOT NULL'; }
            else if ((string)$filters['arch'] === '0') { $where[] = 'nr.archived_at IS NULL'; }
        }
        $sql = 'SELECT nr.id as rid, n.*,
                       (nr.read_at IS NOT NULL) as lida,
                       (nr.archived_at IS NOT NULL) as arquivada
                FROM notification_recipients nr
                JOIN notifications n ON n.id = nr.notification_id
                WHERE '.implode(' AND ', $where).'
                ORDER BY n.created_at DESC
                LIMIT :lim OFFSET :off';
        $st = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markReadFor(int $userId, int $notifId): void
    {
        $st = $this->db->prepare('UPDATE notification_recipients SET read_at = NOW() WHERE user_id=:u AND notification_id=:n');
        $st->execute([':u'=>$userId, ':n'=>$notifId]);
    }

    public function markUnreadFor(int $userId, int $notifId): void
    {
        $st = $this->db->prepare('UPDATE notification_recipients SET read_at = NULL WHERE user_id=:u AND notification_id=:n');
        $st->execute([':u'=>$userId, ':n'=>$notifId]);
    }

    public function archiveFor(int $userId, int $notifId): void
    {
        $st = $this->db->prepare('UPDATE notification_recipients SET archived_at = NOW() WHERE user_id=:u AND notification_id=:n');
        $st->execute([':u'=>$userId, ':n'=>$notifId]);
    }

    public function delete(int $notifId): void
    {
        $this->db->prepare('DELETE FROM notification_recipients WHERE notification_id=:n')->execute([':n'=>$notifId]);
        $this->db->prepare('DELETE FROM notifications WHERE id=:n')->execute([':n'=>$notifId]);
    }
}

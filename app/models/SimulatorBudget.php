<?php
namespace Models;

use Core\Model;
use PDO;
use Models\Client;

class SimulatorBudget extends Model
{
    public function createForUser(int $userId, string $name, array $payload): int
    {
        $st = $this->db->prepare('INSERT INTO simulator_budgets (user_id, name, data_json, created_at) VALUES (:uid, :name, :data, NOW())');
        $st->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':data' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateForUser(int $id, int $userId, string $name, array $payload): void
    {
        $st = $this->db->prepare('UPDATE simulator_budgets SET name=:name, data_json=:data, updated_at=NOW() WHERE id=:id AND user_id=:uid');
        $st->execute([
            ':id' => $id,
            ':uid' => $userId,
            ':name' => $name,
            ':data' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM simulator_budgets WHERE id=:id AND user_id=:uid');
        $st->execute([':id'=>$id, ':uid'=>$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Busca um orçamento pelo ID, sem filtrar por usuário.
     * Usado por administradores/gerentes para abrir orçamentos de outros usuários.
     */
    public function findById(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM simulator_budgets WHERE id=:id');
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listForUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT id, name, created_at, updated_at, paid, paid_at FROM simulator_budgets WHERE user_id=:uid ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off');
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countForUser(int $userId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) c FROM simulator_budgets WHERE user_id=:uid');
        $st->execute([':uid'=>$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['c'] ?? 0);
    }

    /**
     * Lista todos os orçamentos pagos em um intervalo de data de pagamento (paid_at) para todos os usuários.
     * Usado pelo relatório consolidado de produtos do simulador.
     */
    public function listPaidInRange(string $fromDate, string $toDate): array
    {
        $sql = 'SELECT id, user_id, name, paid_at, data_json FROM simulator_budgets WHERE paid = 1';
        $params = [];
        if ($fromDate !== '' && $toDate !== '') {
            $sql .= ' AND paid_at BETWEEN :from AND :to';
            $params[':from'] = $fromDate . ' 00:00:00';
            $params[':to'] = $toDate . ' 23:59:59';
        } elseif ($fromDate !== '') {
            $sql .= ' AND paid_at >= :from';
            $params[':from'] = $fromDate . ' 00:00:00';
        } elseif ($toDate !== '') {
            $sql .= ' AND paid_at <= :to';
            $params[':to'] = $toDate . ' 23:59:59';
        }
        $sql .= ' ORDER BY paid_at ASC, id ASC';
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function duplicateForUser(int $id, int $userId, ?string $newName = null): ?int
    {
        $orig = $this->findForUser($id, $userId);
        if (!$orig) return null;
        $name = $newName ?: ($orig['name'].' (cópia)');
        $payload = json_decode($orig['data_json'] ?? '[]', true) ?: [];
        return $this->createForUser($userId, $name, $payload);
    }

    public function deleteForUser(int $id, int $userId): void
    {
        $st = $this->db->prepare('DELETE FROM simulator_budgets WHERE id=:id AND user_id=:uid');
        $st->execute([':id'=>$id, ':uid'=>$userId]);
    }

    public function setPaidForUser(int $id, int $userId, bool $paid, ?string $paidAt = null): void
    {
        // Carrega estado atual para saber se estávamos pagos ou não antes
        $stSel = $this->db->prepare('SELECT paid, data_json FROM simulator_budgets WHERE id = :id AND user_id = :uid');
        $stSel->execute([':id' => $id, ':uid' => $userId]);
        $row = $stSel->fetch(PDO::FETCH_ASSOC) ?: null;
        $wasPaid = $row && (int)($row['paid'] ?? 0) === 1;

        if ($paid) {
            $sql = 'UPDATE simulator_budgets SET paid=1, paid_at=:paid_at, updated_at=NOW() WHERE id=:id AND user_id=:uid';
        } else {
            $sql = 'UPDATE simulator_budgets SET paid=0, paid_at=NULL, updated_at=NOW() WHERE id=:id AND user_id=:uid';
        }
        $st = $this->db->prepare($sql);
        $params = [
            ':id' => $id,
            ':uid' => $userId,
        ];
        if ($paid) {
            $params[':paid_at'] = $paidAt ?: date('Y-m-d H:i:s');
        }
        $st->execute($params);

        // Se acabou de ser marcado como pago (antes era não pago), credita cashback para o cliente, se houver
        if ($paid && !$wasPaid && $row && !empty($row['data_json'])) {
            $payload = json_decode($row['data_json'], true) ?: [];
            $clientId = (int)($payload['cliente_id'] ?? 0);
            $cashbackUSD = (float)($payload['cashback_usd'] ?? 0);
            if ($clientId > 0 && $cashbackUSD > 0) {
                try {
                    $clientModel = new Client();
                    $clientModel->addCashbackUsd($clientId, $cashbackUSD);
                } catch (\Throwable $e) {
                    // não deixa o erro de cashback quebrar a marcação de pago
                }
            }
        }
    }
}

<?php
namespace Models;

use Core\Model;
use PDO;

class Container extends Model
{
    public static function statuses(): array
    {
        return ['Em preparo','Enviado','Enviado / Faturado','Cancelado'];
    }

    public function compute(array $data): array
    {
        // Settings
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $rate = $set ? (float)$set->get('usd_rate', '5.83') : 5.83;
        if ($rate <= 0) $rate = 5.83;
        $lbsPerKg = $set ? (float)$set->get('lbs_per_kg', '2.2') : 2.2;
        if ($lbsPerKg <= 0) $lbsPerKg = 2.2;

        $pesoKg = (float)($data['peso_kg'] ?? 0);
        $pesoLbs = $pesoKg * $lbsPerKg;
        // Base rate by tier
        if ($pesoLbs <= 100) $base = 21.82;
        elseif ($pesoLbs <= 500) $base = 18.55;
        elseif ($pesoLbs <= 1000) $base = 15.58;
        elseif ($pesoLbs <= 3000) $base = 14.02;
        elseif ($pesoLbs <= 5000) $base = 12.62;
        elseif ($pesoLbs <= 7500) $base = 11.36;
        else $base = 11.36;
        $transporteCaminhaoUSD = 17 * $base;

        $aereoUSD = $pesoKg * 4.0;
        $transpMercadoriaUSD = (float)($data['transporte_mercadoria_usd'] ?? 0);
        $transpAeroportoCorreiosBRL = (float)($data['transporte_aeroporto_correios_brl'] ?? 0);
        $transpAeroportoCorreiosUSD = $rate > 0 ? $transpAeroportoCorreiosBRL / $rate : 0.0;

        $valorFinalUSD = $aereoUSD + $transporteCaminhaoUSD + $transpMercadoriaUSD + $transpAeroportoCorreiosUSD;
        $valorFinalBRL = $valorFinalUSD * $rate;

        return [
            'peso_lbs' => round($pesoLbs, 2),
            'transporte_caminhao_usd' => round($transporteCaminhaoUSD, 2),
            'aereo_usd' => round($aereoUSD, 2),
            'valor_debitado_final_usd' => round($valorFinalUSD, 2),
            'valor_debitado_final_brl' => round($valorFinalBRL, 2),
        ];
    }

    public function create(array $data): int
    {
        $calc = $this->compute($data);
        $stmt = $this->db->prepare('INSERT INTO containers (
            utilizador_id, invoice_id, status, created_at,
            peso_kg, peso_lbs,
            transporte_aeroporto_correios_brl, transporte_caminhao_usd, transporte_mercadoria_usd,
            aereo_usd, valor_debitado_final_usd, valor_debitado_final_brl,
            vendas_ids
        ) VALUES (
            :uid, :invoice, :status, :created_at,
            :peso_kg, :peso_lbs,
            :tac_brl, :tc_usd, :tm_usd,
            :aereo_usd, :final_usd, :final_brl,
            :vendas_ids
        )');
        $stmt->execute([
            ':uid' => $data['utilizador_id'] ?? null,
            ':invoice' => $data['invoice_id'] ?? null,
            ':status' => $data['status'] ?? 'Em preparo',
            ':created_at' => $data['created_at'] ?? date('Y-m-d'),
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':peso_lbs' => $calc['peso_lbs'],
            ':tac_brl' => (float)($data['transporte_aeroporto_correios_brl'] ?? 0),
            ':tc_usd' => $calc['transporte_caminhao_usd'],
            ':tm_usd' => (float)($data['transporte_mercadoria_usd'] ?? 0),
            ':aereo_usd' => $calc['aereo_usd'],
            ':final_usd' => $calc['valor_debitado_final_usd'],
            ':final_brl' => $calc['valor_debitado_final_brl'],
            ':vendas_ids' => $data['vendas_ids'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $calc = $this->compute($data);
        $stmt = $this->db->prepare('UPDATE containers SET
            utilizador_id = :uid,
            invoice_id = :invoice,
            status = :status,
            created_at = :created_at,
            peso_kg = :peso_kg,
            peso_lbs = :peso_lbs,
            transporte_aeroporto_correios_brl = :tac_brl,
            transporte_caminhao_usd = :tc_usd,
            transporte_mercadoria_usd = :tm_usd,
            aereo_usd = :aereo_usd,
            valor_debitado_final_usd = :final_usd,
            valor_debitado_final_brl = :final_brl,
            vendas_ids = :vendas_ids
        WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':uid' => $data['utilizador_id'] ?? null,
            ':invoice' => $data['invoice_id'] ?? null,
            ':status' => $data['status'] ?? 'Em preparo',
            ':created_at' => $data['created_at'] ?? date('Y-m-d'),
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':peso_lbs' => $calc['peso_lbs'],
            ':tac_brl' => (float)($data['transporte_aeroporto_correios_brl'] ?? 0),
            ':tc_usd' => $calc['transporte_caminhao_usd'],
            ':tm_usd' => (float)($data['transporte_mercadoria_usd'] ?? 0),
            ':aereo_usd' => $calc['aereo_usd'],
            ':final_usd' => $calc['valor_debitado_final_usd'],
            ':final_brl' => $calc['valor_debitado_final_brl'],
            ':vendas_ids' => $data['vendas_ids'] ?? null,
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM containers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function list(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM containers ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM containers WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function costsInPeriodUSD(string $from, string $to): float
    {
        // Sum valor_debitado_final_usd by created_at date range
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(valor_debitado_final_usd),0) v FROM containers WHERE created_at BETWEEN :f AND :t');
        $stmt->execute([':f' => $from, ':t' => $to]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return (float)($row['v'] ?? 0);
    }
}

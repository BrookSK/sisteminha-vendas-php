<?php
namespace Models;

use Core\Model;
use PDO;

class NewCommission extends Model
{
    private function percentByBrutoBrl(float $brutoBrl): float
    {
        if ($brutoBrl < 45000.0) return 0.0;
        if ($brutoBrl < 75000.0) return 0.05;
        if ($brutoBrl < 200000.0) return 0.074;
        if ($brutoBrl < 250000.0) return 0.09;
        if ($brutoBrl < 400000.0) return 0.10;
        if ($brutoBrl < 500000.0) return 0.12;
        return 0.14;
    }
    public function computeRange(string $from, string $to, ?int $sellerId = null): array
    {
        $fromDate = substr($from, 0, 10);
        $toDate = substr($to, 0, 10);

        $whereSeller = '';
        $p = [':from' => $fromDate, ':to' => $toDate];
        if ($sellerId) {
            $whereSeller = ' AND vendedor_id = :sid';
            $p[':sid'] = $sellerId;
        }

        $sqlIntl = "SELECT vendedor_id as uid,
                COALESCE(SUM(gross_usd),0) as gross_usd,
                COALESCE(SUM(gross_brl),0) as gross_brl,
                COALESCE(SUM(produtos_compra_usd),0) as products_usd,
                COALESCE(SUM((gross_usd - COALESCE(produtos_compra_usd,0))),0) as liquido_novo_usd,
                COALESCE(SUM((gross_usd - COALESCE(produtos_compra_usd,0)) * COALESCE(exchange_rate_used, taxa_dolar, 0)),0) as liquido_novo_brl
            FROM vendas_internacionais
            WHERE data_lancamento BETWEEN :from AND :to{$whereSeller}
            GROUP BY vendedor_id";

        $sqlNat = "SELECT vendedor_id as uid,
                COALESCE(SUM(gross_usd),0) as gross_usd,
                COALESCE(SUM(gross_brl),0) as gross_brl,
                COALESCE(SUM(produtos_compra_usd),0) as products_usd,
                COALESCE(SUM((gross_usd - COALESCE(produtos_compra_usd,0))),0) as liquido_novo_usd,
                COALESCE(SUM((gross_usd - COALESCE(produtos_compra_usd,0)) * COALESCE(exchange_rate_used, taxa_dolar, 0)),0) as liquido_novo_brl
            FROM vendas_nacionais
            WHERE data_lancamento BETWEEN :from AND :to{$whereSeller}
            GROUP BY vendedor_id";

        $stmt1 = $this->db->prepare($sqlIntl);
        $stmt1->execute($p);
        $intl = $stmt1->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->db->prepare($sqlNat);
        $stmt2->execute($p);
        $nat = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $agg = [];
        $add = function(array $r) use (&$agg): void {
            $uid = (int)($r['uid'] ?? 0);
            if ($uid <= 0) return;
            if (!isset($agg[$uid])) {
                $agg[$uid] = [
                    'uid' => $uid,
                    'gross_usd' => 0.0,
                    'gross_brl' => 0.0,
                    'products_usd' => 0.0,
                    'liquido_novo_usd' => 0.0,
                    'liquido_novo_brl' => 0.0,
                ];
            }
            $agg[$uid]['gross_usd'] += (float)($r['gross_usd'] ?? 0);
            $agg[$uid]['gross_brl'] += (float)($r['gross_brl'] ?? 0);
            $agg[$uid]['products_usd'] += (float)($r['products_usd'] ?? 0);
            $agg[$uid]['liquido_novo_usd'] += (float)($r['liquido_novo_usd'] ?? 0);
            $agg[$uid]['liquido_novo_brl'] += (float)($r['liquido_novo_brl'] ?? 0);
        };

        foreach ($intl as $r) $add($r);
        foreach ($nat as $r) $add($r);

        $users = $this->db->query("SELECT id, name, email, role, ativo FROM usuarios ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $usersById = [];
        foreach ($users as $u) { $usersById[(int)$u['id']] = $u; }

        $outItems = [];
        foreach ($agg as $uid => $vals) {
            $u = $usersById[$uid] ?? null;
            if (!$u) continue;
            $brutoBrl = (float)$vals['gross_brl'];
            $pct = $this->percentByBrutoBrl($brutoBrl);
            $commissionBrl = (float)$vals['liquido_novo_brl'] * $pct;
            $outItems[] = [
                'seller_id' => $uid,
                'seller_name' => (string)($u['name'] ?? ''),
                'seller_email' => (string)($u['email'] ?? ''),
                'seller_role' => (string)($u['role'] ?? ''),
                'seller_ativo' => (int)($u['ativo'] ?? 0),
                'bruto_total_usd' => (float)$vals['gross_usd'],
                'bruto_total_brl' => $brutoBrl,
                'produtos_compra_usd' => (float)$vals['products_usd'],
                'liquido_novo_usd' => (float)$vals['liquido_novo_usd'],
                'liquido_novo_brl' => (float)$vals['liquido_novo_brl'],
                'percent' => $pct,
                'comissao_brl' => $commissionBrl,
            ];
        }

        usort($outItems, function($a, $b) {
            return ($b['comissao_brl'] ?? 0) <=> ($a['comissao_brl'] ?? 0);
        });

        $sumBrutoBrl = 0.0; $sumLiquidoBrl = 0.0; $sumComBrl = 0.0;
        foreach ($outItems as $it) {
            $sumBrutoBrl += (float)($it['bruto_total_brl'] ?? 0);
            $sumLiquidoBrl += (float)($it['liquido_novo_brl'] ?? 0);
            $sumComBrl += (float)($it['comissao_brl'] ?? 0);
        }

        return [
            'items' => $outItems,
            'company' => [
                'bruto_total_brl' => $sumBrutoBrl,
                'liquido_novo_brl' => $sumLiquidoBrl,
                'comissao_brl' => $sumComBrl,
            ],
        ];
    }
}

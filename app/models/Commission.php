<?php
namespace Models;

use Core\Model;
use PDO;

/**
 * Commission calculation model.
 * - Computes individual commission based on seller's total gross/liquid in a period.
 * - Applies team bonus if team gross >= 50,000 USD and divides 5% by active sellers.
 * - Persists monthly summaries in `comissoes` table using period format YYYY-MM.
 */
class Commission extends Model
{
    const TEAM_GOAL_USD = 50000.0;

    /** Returns [from, to] for a given period string YYYY-MM. */
    public function monthRange(string $ym): array
    {
        $from = date('Y-m-01 00:00:00', strtotime($ym . '-01'));
        $to = date('Y-m-t 23:59:59', strtotime($ym . '-01'));
        return [$from, $to];
    }

    /** Aggregate sales per active seller in [from, to] from all sources. Returns keyed by user id. */
    public function aggregateByUser(string $from, string $to): array
    {
        // Consider only active sellers/managers for commission. Admins are excluded from bonus by default.
        $users = $this->db->query("SELECT id, name, email, role, ativo FROM usuarios")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $eligible = [];
        foreach ($users as $u) {
            $eligible[(int)$u['id']] = $u;
        }

        // Legacy vendas table (if still populated)
        $stmt = $this->db->prepare('SELECT usuario_id as uid,
                   COALESCE(SUM(bruto_usd),0) as bruto_total,
                   COALESCE(SUM(liquido_usd),0) as liquido_total
               FROM vendas
               WHERE created_at BETWEEN :from AND :to
               GROUP BY usuario_id');
        $stmt->execute([':from' => $from, ':to' => $to]);
        $rowsLegacy = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // International sales (vendedor_id)
        $stmt2 = $this->db->prepare('SELECT vendedor_id as uid,
                   COALESCE(SUM(total_bruto_usd),0) as bruto_total,
                   COALESCE(SUM(total_liquido_usd),0) as liquido_total
               FROM vendas_internacionais
               WHERE data_lancamento BETWEEN :from AND :to
               GROUP BY vendedor_id');
        $stmt2->execute([':from' => substr($from,0,10), ':to' => substr($to,0,10)]);
        $rowsIntl = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // National sales (vendedor_id)
        $stmt3 = $this->db->prepare('SELECT vendedor_id as uid,
                   COALESCE(SUM(total_bruto_usd),0) as bruto_total,
                   COALESCE(SUM(total_liquido_usd),0) as liquido_total
               FROM vendas_nacionais
               WHERE data_lancamento BETWEEN :from AND :to
               GROUP BY vendedor_id');
        $stmt3->execute([':from' => substr($from,0,10), ':to' => substr($to,0,10)]);
        $rowsNat = $stmt3->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Merge by uid
        $aggRows = [];
        $add = function($r) use (&$aggRows) {
            $uid = (int)($r['uid'] ?? 0);
            if ($uid<=0) return;
            if (!isset($aggRows[$uid])) { $aggRows[$uid] = ['bruto_total'=>0.0,'liquido_total'=>0.0]; }
            $aggRows[$uid]['bruto_total'] += (float)($r['bruto_total'] ?? 0);
            $aggRows[$uid]['liquido_total'] += (float)($r['liquido_total'] ?? 0);
        };
        foreach ($rowsLegacy as $r) $add($r); // uses alias uid, bruto_total, liquido_total
        foreach ($rowsIntl as $r) $add($r);
        foreach ($rowsNat as $r) $add($r);

        $out = [];
        foreach ($aggRows as $uid => $vals) {
            if ($uid <= 0) { continue; }
            $u = $eligible[$uid] ?? null;
            if (!$u) { continue; }
            $out[$uid] = [
                'user' => $u,
                'bruto_total' => (float)$vals['bruto_total'],
                'liquido_total' => (float)$vals['liquido_total'],
            ];
        }
        // Ensure users with zero sales can appear (esp. for admin view)
        foreach ($eligible as $uid => $u) {
            if (!isset($out[$uid])) {
                $out[$uid] = [
                    'user' => $u,
                    'bruto_total' => 0.0,
                    'liquido_total' => 0.0,
                ];
            }
        }
        return $out;
    }

    /** Compute commissions for a range. Returns [items, team] without persisting. */
    public function computeRange(string $from, string $to): array
    {
        $agg = $this->aggregateByUser($from, $to);
        $teamBruto = 0.0;
        $activeCount = 0;
        foreach ($agg as $row) {
            // Conta vendedores/gerentes ativos como elegíveis para rateio do bônus
            $role = $row['user']['role'] ?? 'seller';
            if ((int)($row['user']['ativo'] ?? 0) === 1 && in_array($role, ['seller','manager'], true)) {
                $activeCount++;
            }
            // Bruto da equipe inclui todos (inclusive 'organic') para efeito de meta e custo global
            $teamBruto += (float)$row['bruto_total'];
        }
        // Global cost allocation from Settings (applied on team gross)
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $costRate = $set ? (float)$set->get('cost_rate', '0.15') : 0.15;
        if ($costRate < 0) $costRate = 0; if ($costRate > 1) $costRate = 1;
        $teamCostSettings = $teamBruto * $costRate;

        // Explicit costs from custos table in the period
        // Sum fixed USD values and percentage values
        $stmtC = $this->db->prepare("SELECT 
              COALESCE(SUM(CASE WHEN valor_tipo <> 'percent' OR valor_tipo IS NULL THEN valor_usd ELSE 0 END),0) AS fixed_usd,
              COALESCE(SUM(CASE WHEN valor_tipo = 'percent' THEN valor_percent ELSE 0 END),0) AS percent_sum
            FROM custos
            WHERE data BETWEEN :from_d AND :to_d");
        $stmtC->execute([':from_d' => substr($from,0,10), ':to_d' => substr($to,0,10)]);
        $rowC = $stmtC->fetch(PDO::FETCH_ASSOC) ?: [];
        $fixedUsd = (float)($rowC['fixed_usd'] ?? 0);
        $percentSum = (float)($rowC['percent_sum'] ?? 0); // e.g., 5.0 means 5%
        $teamCostPercent = $teamBruto * ($percentSum / 100.0);

        // Total team cost = settings rate cost + explicit custos
        $teamCost = $teamCostSettings + $fixedUsd + $teamCostPercent;
        $applyBonus = $teamBruto >= self::TEAM_GOAL_USD;
        $bonusRate = $applyBonus && $activeCount > 0 ? (0.05 / $activeCount) : 0.0;

        $items = [];
        // Dollar rate for BRL conversions (admin setting)
        try { $setRate = new Setting(); } catch (\Throwable $e) { $setRate = null; }
        $usdRate = $setRate ? (float)$setRate->get('usd_rate', '5.83') : 5.83;
        if ($usdRate <= 0) { $usdRate = 5.83; }
        $teamBrutoBRL = $teamBruto * $usdRate;
        $metaEquipeBRL = 50000.0 * $usdRate; // 50k USD em BRL

        foreach ($agg as $uid => $row) {
            $liquido = (float)$row['liquido_total'];
            $bruto = (float)$row['bruto_total'];
            // Allocate share of total team cost proportionally to gross
            $allocatedCost = ($teamBruto > 0) ? ($teamCost * ($bruto / $teamBruto)) : 0.0;
            $liquidoAfterCost = max(0.0, $liquido - $allocatedCost);
            // Convert to BRL for rule thresholds and amounts
            $bruto_brl = $bruto * $usdRate;
            $liquido_apurado_brl = $liquidoAfterCost * $usdRate;
            // Individual commission percent based on BRL thresholds (30k USD equivalent)
            if ($bruto_brl <= 30000.0 * $usdRate) {
                $perc = 0.15;
            } elseif ($bruto_brl <= 45000.0 * $usdRate) {
                $perc = 0.25;
            } else {
                $perc = 0.25;
            }
            $individual_brl = round($liquido_apurado_brl * $perc, 2);
            // Elegibilidade à comissão: sellers e managers ativos
            $role = $row['user']['role'] ?? '';
            $isSellerActive = in_array($role, ['seller','manager'], true) && ((int)($row['user']['ativo'] ?? 0) === 1);
            // Team bonus on BRL liquid if team reached goal (somente sellers ativos)
            $bonus_brl = 0.0;
            if ($isSellerActive && $teamBrutoBRL >= $metaEquipeBRL) {
                $bonus_brl = round($liquido_apurado_brl * $bonusRate, 2);
            }
            $final_brl = round($individual_brl + $bonus_brl, 2);
            // Map BRL back to USD legacy fields
            $individual = $isSellerActive ? round($usdRate > 0 ? ($individual_brl / $usdRate) : 0, 2) : 0.0;
            $bonus = $isSellerActive ? round($usdRate > 0 ? ($bonus_brl / $usdRate) : 0, 2) : 0.0;
            $final = $isSellerActive ? round($usdRate > 0 ? ($final_brl / $usdRate) : 0, 2) : 0.0;
            $items[] = [
                'vendedor_id' => (int)$uid,
                'user' => $row['user'],
                'bruto_total' => round($bruto, 2),
                'liquido_total' => round($liquido, 2),
                'allocated_cost' => round($allocatedCost, 2),
                'liquido_apurado' => round($liquidoAfterCost, 2),
                'comissao_individual' => $individual,
                'bonus' => $bonus,
                'comissao_final' => $final,
                // BRL fields for UI/reporting
                'bruto_total_brl' => round($bruto_brl, 2),
                'liquido_total_brl' => round($liquido * $usdRate, 2),
                'allocated_cost_brl' => round($allocatedCost * $usdRate, 2),
                'liquido_apurado_brl' => round($liquido_apurado_brl, 2),
                'comissao_individual_brl' => $individual_brl,
                'bonus_brl' => $bonus_brl,
                'comissao_final_brl' => $final_brl,
            ];
        }
        // Sort by final commission desc for nicer admin view
        usort($items, function($a,$b){ return $b['comissao_final'] <=> $a['comissao_final']; });

        return [
            'items' => $items,
            'team' => [
                'team_bruto_total' => round($teamBruto, 2),
                // Effective total cost rate including settings, fixed and percent custos
                'team_cost_rate' => ($teamBruto > 0 ? ($teamCost / $teamBruto) : 0.0),
                'team_cost_total' => round($teamCost, 2),
                'team_cost_settings_rate' => $costRate,
                'team_cost_fixed_usd' => round($fixedUsd, 2),
                'team_cost_percent_rate' => $percentSum / 100.0,
                'team_cost_percent_total' => round($teamCostPercent, 2),
                'apply_bonus' => ($teamBrutoBRL >= $metaEquipeBRL),
                'active_count' => $activeCount,
                'bonus_rate' => $bonusRate,
                'team_bruto_total_brl' => round($teamBrutoBRL, 2),
                'meta_equipe_brl' => round($metaEquipeBRL, 2),
            ]
        ];
    }

    /** Recalculate and persist monthly commissions for period YYYY-MM. */
    public function recalcMonthly(string $ym): void
    {
        [$from, $to] = $this->monthRange($ym);
        $calc = $this->computeRange($from, $to);
        $this->persistMonthly($ym, $calc['items']);
    }

    /** Upsert rows into comissoes for given month. */
    public function persistMonthly(string $ym, array $items): void
    {
        $sql = 'INSERT INTO comissoes (vendedor_id, periodo, bruto_total, liquido_total, comissao_individual, bonus, comissao_final, created_at, updated_at)
                VALUES (:vid, :periodo, :bruto, :liquido, :individual, :bonus, :final, NOW(), NOW())
                ON DUPLICATE KEY UPDATE bruto_total=VALUES(bruto_total), liquido_total=VALUES(liquido_total),
                    comissao_individual=VALUES(comissao_individual), bonus=VALUES(bonus), comissao_final=VALUES(comissao_final), updated_at=NOW()';
        $stmt = $this->db->prepare($sql);
        foreach ($items as $it) {
            $stmt->execute([
                ':vid' => $it['vendedor_id'],
                ':periodo' => $ym,
                ':bruto' => $it['bruto_total'],
                ':liquido' => $it['liquido_total'],
                ':individual' => $it['comissao_individual'],
                ':bonus' => $it['bonus'],
                ':final' => $it['comissao_final'],
            ]);
        }
    }

    /** Load persisted monthly commissions for admin page. */
    public function loadMonthly(string $ym): array
    {
        $stmt = $this->db->prepare('SELECT c.*, u.name, u.email, u.role, u.ativo
            FROM comissoes c
            JOIN usuarios u ON u.id = c.vendedor_id
            WHERE c.periodo = :p
            ORDER BY c.comissao_final DESC');
        $stmt->execute([':p' => $ym]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Historical monthly rows for a single seller. */
    public function historyForSeller(int $sellerId, int $limit = 12): array
    {
        $stmt = $this->db->prepare('SELECT * FROM comissoes WHERE vendedor_id = :id ORDER BY periodo DESC LIMIT :lim');
        $stmt->bindValue(':id', $sellerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Detailed costs for a given range, including settings cost rate and explicit custos rows. */
    public function costsInRange(string $from, string $to): array
    {
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $costRate = $set ? (float)$set->get('cost_rate', '0.15') : 0.15;
        if ($costRate < 0) $costRate = 0; if ($costRate > 1) $costRate = 1;

        $stmt = $this->db->prepare("SELECT id, data, descricao, valor_tipo, valor_usd, valor_percent FROM custos WHERE data BETWEEN :from_d AND :to_d ORDER BY data ASC, id ASC");
        $stmt->execute([':from_d' => substr($from,0,10), ':to_d' => substr($to,0,10)]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $fixedUsd = 0.0; $percentSum = 0.0;
        foreach ($rows as $r) {
            $tipo = $r['valor_tipo'] ?? null;
            if ($tipo === 'percent') {
                $percentSum += (float)($r['valor_percent'] ?? 0);
            } else {
                $fixedUsd += (float)($r['valor_usd'] ?? 0);
            }
        }
        return [
            'settings_cost_rate' => $costRate,
            'explicit_costs' => $rows,
            'explicit_fixed_usd' => round($fixedUsd, 2),
            'explicit_percent_sum' => round($percentSum, 2),
        ];
    }

    /** Per-source sums for a seller within range: legacy, international, national. */
    public function sellerSourceSums(int $sellerId, string $from, string $to): array
    {
        $out = [
            'legacy' => ['bruto_total'=>0.0,'liquido_total'=>0.0],
            'intl' => ['bruto_total'=>0.0,'liquido_total'=>0.0],
            'nat' => ['bruto_total'=>0.0,'liquido_total'=>0.0],
        ];
        // Legacy vendas
        $stmt1 = $this->db->prepare('SELECT COALESCE(SUM(bruto_usd),0) as b, COALESCE(SUM(liquido_usd),0) as l FROM vendas WHERE usuario_id=:id AND created_at BETWEEN :f AND :t');
        $stmt1->execute([':id'=>$sellerId, ':f'=>$from, ':t'=>$to]);
        if ($r=$stmt1->fetch(PDO::FETCH_ASSOC)) { $out['legacy']['bruto_total']=(float)$r['b']; $out['legacy']['liquido_total']=(float)$r['l']; }
        // International
        $stmt2 = $this->db->prepare('SELECT COALESCE(SUM(total_bruto_usd),0) as b, COALESCE(SUM(total_liquido_usd),0) as l FROM vendas_internacionais WHERE vendedor_id=:id AND data_lancamento BETWEEN :f AND :t');
        $stmt2->execute([':id'=>$sellerId, ':f'=>substr($from,0,10), ':t'=>substr($to,0,10)]);
        if ($r=$stmt2->fetch(PDO::FETCH_ASSOC)) { $out['intl']['bruto_total']=(float)$r['b']; $out['intl']['liquido_total']=(float)$r['l']; }
        // National
        $stmt3 = $this->db->prepare('SELECT COALESCE(SUM(total_bruto_usd),0) as b, COALESCE(SUM(total_liquido_usd),0) as l FROM vendas_nacionais WHERE vendedor_id=:id AND data_lancamento BETWEEN :f AND :t');
        $stmt3->execute([':id'=>$sellerId, ':f'=>substr($from,0,10), ':t'=>substr($to,0,10)]);
        if ($r=$stmt3->fetch(PDO::FETCH_ASSOC)) { $out['nat']['bruto_total']=(float)$r['b']; $out['nat']['liquido_total']=(float)$r['l']; }
        // Totals
        $out['total'] = [
            'bruto_total' => $out['legacy']['bruto_total'] + $out['intl']['bruto_total'] + $out['nat']['bruto_total'],
            'liquido_total' => $out['legacy']['liquido_total'] + $out['intl']['liquido_total'] + $out['nat']['liquido_total'],
        ];
        return $out;
    }
}

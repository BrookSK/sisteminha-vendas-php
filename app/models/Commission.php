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

    /** Default period label (YYYY-MM) for UI/controllers respecting 10th-9th cycle. */
    public static function defaultPeriod(): string
    {
        $day = (int)date('d');
        if ($day <= 9) {
            return date('Y-m', strtotime('first day of last month'));
        }
        return date('Y-m');
    }

    /** Returns [from, to] for a given period string YYYY-MM. */
    public function monthRange(string $ym): array
    {
        // Custom commission cycle: from the 10th of the given month to the 9th of the next month
        $startBase = strtotime($ym . '-10');
        // Start at 10th 00:00:00 of the given month
        $from = date('Y-m-10 00:00:00', $startBase);
        // End at 9th 23:59:59 of the next month
        $endBase = strtotime('+1 month', $startBase);
        $to = date('Y-m-09 23:59:59', $endBase);
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
        $teamLiquido = 0.0;
        $activeCount = 0; // ativos para bônus (mantido)
        $activeCostSplit = 0; // legado: contagem anterior (mantido para compatibilidade de saída)
        // Novos acumuladores para a nova regra de custos
        $perUserBruto = [];
        $perUserRole = [];
        $perUserActive = [];
        $activeNonTraineeIds = []; // roles seller/manager
        $activeTraineeIds = [];
        $activeCostPayerIds = [];
        $totalVendasEquipe = 0.0; // soma de bruto dos ativos elegíveis (seller/manager/trainee)
        foreach ($agg as $uidLoop => $row) {
            // Conta vendedores/trainees/gerentes ativos como elegíveis para rateio do bônus
            $role = $row['user']['role'] ?? 'seller';
            $hasSale = ((float)($row['bruto_total'] ?? 0) > 0.0);
            if ((int)($row['user']['ativo'] ?? 0) === 1 && (
                in_array($role, ['seller','manager'], true) ||
                ($role === 'trainee' && $hasSale)
            )) {
                $activeCount++;
            }
            // Contagem antiga (igualitária) mantida para compatibilidade em campos de saída
            if ((int)($row['user']['ativo'] ?? 0) === 1 && in_array($role, ['seller','trainee','manager'], true)) {
                $activeCostSplit++;
            }
            // Bruto da equipe inclui todos (inclusive 'organic') para efeito de meta e custo global
            $teamBruto += (float)$row['bruto_total'];
            $teamLiquido += (float)$row['liquido_total'];

            // Guardar informações por usuário para cálculo de rateio de custos
            $uidI = (int)$uidLoop;
            $isActiveEligible = ((int)($row['user']['ativo'] ?? 0) === 1) && in_array($role, ['seller','trainee','manager'], true);
            $perUserBruto[$uidI] = (float)$row['bruto_total'];
            $perUserRole[$uidI] = $role;
            $perUserActive[$uidI] = $isActiveEligible;
            if ($isActiveEligible) {
                $totalVendasEquipe += (float)$row['bruto_total'];
                if ($role === 'trainee') { $activeTraineeIds[] = $uidI; }
                if (in_array($role, ['seller','manager'], true)) { $activeNonTraineeIds[] = $uidI; }
                // Todos os ativos (seller/manager/trainee) pagam o custo global igualmente
                $activeCostPayerIds[] = $uidI;
            }
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

        // Nova regra de rateio de custos (atualizada):
        // - Imposto (configuração 15%): trainee paga somente sobre sua própria venda (bruto * cost_rate).
        //   O restante do imposto é dividido igualmente entre vendedores/gerentes ativos.
        // - Custos explícitos (fixos + percentuais): divididos apenas entre vendedores/gerentes ativos.
        $explicitTotal = $fixedUsd + $teamCostPercent;
        $countNonTrainee = count($activeNonTraineeIds);
        // Mapa de imposto por trainee (custo global sobre venda própria)
        $traineeSettingsMap = [];
        $traineeSettingsSum = 0.0;
        foreach ($activeTraineeIds as $tid) {
            $val = ($perUserBruto[$tid] ?? 0.0) * $costRate;
            $traineeSettingsMap[$tid] = $val;
            $traineeSettingsSum += $val;
        }
        // Parte restante do imposto é rateada igualmente entre não-trainees ativos
        $remainingSettings = max(0.0, $teamCostSettings - $traineeSettingsSum);
        $settingsShareNonTrainee = ($countNonTrainee > 0) ? ($remainingSettings / $countNonTrainee) : 0.0;
        // Custos explícitos só entre não-trainees
        $explicitShare = ($countNonTrainee > 0) ? ($explicitTotal / $countNonTrainee) : 0.0;

        $sumRateadoUsd = 0.0; // soma dos líquidos rateados (USD)
        $sumCommissionsUsd = 0.0; // soma das comissões (final) em USD
        $sumRateadoBrl = 0.0; // soma dos líquidos rateados (BRL)
        $sumCommissionsBrl = 0.0; // soma das comissões (final) em BRL

        foreach ($agg as $uid => $row) {
            $liquido = (float)$row['liquido_total'];
            $bruto = (float)$row['bruto_total'];
            // Alocação conforme nova regra
            $role = $row['user']['role'] ?? '';
            $isCostEligibleActive = in_array($role, ['seller','trainee','manager'], true) && ((int)($row['user']['ativo'] ?? 0) === 1);
            if ($isCostEligibleActive) {
                if ($role === 'trainee') {
                    $allocatedCost = (float)($traineeSettingsMap[(int)$uid] ?? 0.0);
                } else {
                    $allocatedCost = $settingsShareNonTrainee + $explicitShare;
                }
            } else {
                $allocatedCost = 0.0;
            }
            // permitir líquido negativo após rateio para refletir início do mês negativo
            $liquidoAfterCost = ($liquido - $allocatedCost);
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
            $liqApBRL = $liquido_apurado_brl; // alias for clarity
            // comissão não deve ser negativa: base zero se líquido apurado < 0
            $baseComBRL = max(0.0, $liqApBRL);
            $indBRL = $baseComBRL * $perc; // comissão individual em BRL
            $applyBonus = ($teamBrutoBRL >= $metaEquipeBRL);
            $bonusBRL = $applyBonus ? ($baseComBRL * $bonusRate) : 0.0;
            $finalBRL = $indBRL + $bonusBRL;
            // Safety clamps (não permitir comissão negativa)
            if ($indBRL < 0) $indBRL = 0.0;
            if ($bonusBRL < 0) $bonusBRL = 0.0;
            if ($finalBRL < 0) $finalBRL = 0.0;
            $indUSD = ($usdRate>0) ? ($indBRL/$usdRate) : 0.0;
            $bonusUSD = ($usdRate>0) ? ($bonusBRL/$usdRate) : 0.0;
            $finalUSD = ($usdRate>0) ? ($finalBRL/$usdRate) : 0.0;
            if ($indUSD < 0) $indUSD = 0.0;
            if ($bonusUSD < 0) $bonusUSD = 0.0;
            if ($finalUSD < 0) $finalUSD = 0.0;

            // Acumular totais para caixa da empresa
            $sumRateadoUsd += $liquidoAfterCost;
            $sumCommissionsUsd += $finalUSD;
            $sumRateadoBrl += $liquido_apurado_brl;
            $sumCommissionsBrl += $finalBRL;
            $items[] = [
                'vendedor_id' => (int)$uid,
                'user' => $row['user'],
                'bruto_total' => round($bruto, 2),
                'liquido_total' => round($liquido, 2),
                'allocated_cost' => round($allocatedCost, 2),
                'liquido_apurado' => round($liquidoAfterCost, 2),
                'comissao_individual' => round($indUSD, 2),
                'bonus' => round($bonusUSD, 2),
                'comissao_final' => round($finalUSD, 2),
                'percent_individual' => $perc,
                // BRL fields for UI/reporting
                'bruto_total_brl' => round($bruto_brl, 2),
                'liquido_total_brl' => round($liquido * $usdRate, 2),
                'allocated_cost_brl' => round($allocatedCost * $usdRate, 2),
                'liquido_apurado_brl' => round($liquido_apurado_brl, 2),
                'comissao_individual_brl' => round($indBRL, 2),
                'bonus_brl' => round($bonusBRL, 2),
                'comissao_final_brl' => round($finalBRL, 2),
            ];
        }
        // Sort by final commission desc for nicer admin view
        usort($items, function($a,$b){ return $b['comissao_final'] <=> $a['comissao_final']; });

        // Caixa da empresa = soma dos líquidos rateados - soma das comissões
        $companyCashUsd = $sumRateadoUsd - $sumCommissionsUsd;
        $companyCashBrl = $sumRateadoBrl - $sumCommissionsBrl;

        return [
            'items' => $items,
            'team' => [
                'team_bruto_total' => round($teamBruto, 2),
                'team_liquido_total' => round($teamLiquido, 2),
                // Effective total cost rate including settings, fixed and percent custos
                'team_cost_rate' => ($teamBruto > 0 ? ($teamCost / $teamBruto) : 0.0),
                'team_cost_total' => round($teamCost, 2),
                'equal_cost_share_per_active_seller' => round($settingsShareNonTrainee, 2),
                'explicit_cost_share_per_non_trainee' => round($explicitShare, 2),
                'team_cost_settings_rate' => $costRate,
                'team_cost_fixed_usd' => round($fixedUsd, 2),
                'team_cost_percent_rate' => $percentSum / 100.0,
                'team_cost_percent_total' => round($teamCostPercent, 2),
                'team_remaining_cost_to_cover' => round(max(0.0, $teamCost - $teamLiquido), 2),
                'apply_bonus' => ($teamBrutoBRL >= $metaEquipeBRL),
                'active_count' => $activeCount,
                'active_cost_split_count' => $activeCostSplit,
                'non_trainee_active_count' => $countNonTrainee,
                'bonus_rate' => $bonusRate,
                'team_bruto_total_brl' => round($teamBrutoBRL, 2),
                'meta_equipe_brl' => round($metaEquipeBRL, 2),
                'company_cash_usd' => round($companyCashUsd, 2),
                'sum_rateado_usd' => round($sumRateadoUsd, 2),
                'sum_commissions_usd' => round($sumCommissionsUsd, 2),
                'company_cash_brl' => round($companyCashBrl, 2),
                'sum_rateado_brl' => round($sumRateadoBrl, 2),
                'sum_commissions_brl' => round($sumCommissionsBrl, 2),
                'usd_rate' => $usdRate,
            ]
        ];
    }

    /** Recalculate and persist monthly commissions for period YYYY-MM. */
    public function recalcMonthly(string $ym): void
    {
        [$from, $to] = $this->monthRange($ym);
        $calc = $this->computeRange($from, $to);
        $this->persistMonthly($ym, $calc['items']);
        $this->persistMonthlySummary($ym, $calc['team']);
    }

    /** Upsert rows into comissoes for given month with full per-user snapshot. */
    public function persistMonthly(string $ym, array $items): void
    {
        $sql = 'INSERT INTO comissoes (
                    vendedor_id, periodo,
                    vendedor_name, vendedor_email, vendedor_role, vendedor_ativo,
                    bruto_total, liquido_total,
                    allocated_cost, liquido_apurado, percent_individual,
                    comissao_individual, bonus, comissao_final,
                    bruto_total_brl, liquido_total_brl, allocated_cost_brl, liquido_apurado_brl,
                    comissao_individual_brl, bonus_brl, comissao_final_brl,
                    created_at, updated_at
                ) VALUES (
                    :vid, :periodo,
                    :vname, :vemail, :vrole, :vativo,
                    :bruto, :liquido,
                    :allocated_cost, :liquido_apurado, :percent_individual,
                    :individual, :bonus, :final,
                    :bruto_brl, :liquido_brl, :allocated_cost_brl, :liquido_apurado_brl,
                    :individual_brl, :bonus_brl, :final_brl,
                    NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    vendedor_name=VALUES(vendedor_name), vendedor_email=VALUES(vendedor_email), vendedor_role=VALUES(vendedor_role), vendedor_ativo=VALUES(vendedor_ativo),
                    bruto_total=VALUES(bruto_total), liquido_total=VALUES(liquido_total),
                    allocated_cost=VALUES(allocated_cost), liquido_apurado=VALUES(liquido_apurado), percent_individual=VALUES(percent_individual),
                    comissao_individual=VALUES(comissao_individual), bonus=VALUES(bonus), comissao_final=VALUES(comissao_final),
                    bruto_total_brl=VALUES(bruto_total_brl), liquido_total_brl=VALUES(liquido_total_brl), allocated_cost_brl=VALUES(allocated_cost_brl), liquido_apurado_brl=VALUES(liquido_apurado_brl),
                    comissao_individual_brl=VALUES(comissao_individual_brl), bonus_brl=VALUES(bonus_brl), comissao_final_brl=VALUES(comissao_final_brl),
                    updated_at=NOW()';
        $stmt = $this->db->prepare($sql);
        foreach ($items as $it) {
            $u = (array)($it['user'] ?? []);
            $stmt->execute([
                ':vid' => $it['vendedor_id'],
                ':periodo' => $ym,
                ':vname' => $u['name'] ?? null,
                ':vemail' => $u['email'] ?? null,
                ':vrole' => $u['role'] ?? null,
                ':vativo' => isset($u['ativo']) ? (int)$u['ativo'] : null,
                ':bruto' => $it['bruto_total'],
                ':liquido' => $it['liquido_total'],
                ':allocated_cost' => $it['allocated_cost'] ?? 0,
                ':liquido_apurado' => $it['liquido_apurado'] ?? (($it['liquido_total'] ?? 0) - ($it['allocated_cost'] ?? 0)),
                ':percent_individual' => $it['percent_individual'] ?? null,
                ':individual' => $it['comissao_individual'],
                ':bonus' => $it['bonus'],
                ':final' => $it['comissao_final'],
                ':bruto_brl' => $it['bruto_total_brl'] ?? 0,
                ':liquido_brl' => $it['liquido_total_brl'] ?? 0,
                ':allocated_cost_brl' => $it['allocated_cost_brl'] ?? 0,
                ':liquido_apurado_brl' => $it['liquido_apurado_brl'] ?? 0,
                ':individual_brl' => $it['comissao_individual_brl'] ?? 0,
                ':bonus_brl' => $it['bonus_brl'] ?? 0,
                ':final_brl' => $it['comissao_final_brl'] ?? 0,
            ]);
        }
    }

    /** Upsert monthly team summary for a given period, including cost breakdown and counts. */
    public function persistMonthlySummary(string $ym, array $team): void
    {
        $sql = 'INSERT INTO comissoes_resumo (
                    periodo, team_bruto_total, team_liquido_total,
                    company_cash_usd, sum_rateado_usd, sum_commissions_usd,
                    company_cash_brl, sum_rateado_brl, sum_commissions_brl,
                    usd_rate, team_cost_settings_rate, team_cost_total,
                    equal_cost_share_per_active_seller, explicit_cost_share_per_non_trainee,
                    team_cost_fixed_usd, team_cost_percent_rate, team_cost_percent_total,
                    team_remaining_cost_to_cover, apply_bonus, active_count, active_cost_split_count, non_trainee_active_count, bonus_rate,
                    team_bruto_total_brl, meta_equipe_brl,
                    created_at, updated_at
                ) VALUES (
                    :periodo, :tb, :tl, :ccu, :sru, :scu, :ccb, :srb, :scb,
                    :usd_rate, :cost_rate, :team_cost_total,
                    :equal_share, :explicit_share,
                    :cost_fixed, :cost_percent_rate, :cost_percent_total,
                    :remaining_to_cover, :apply_bonus, :active_count, :active_cost_split_count, :non_trainee_active_count, :bonus_rate,
                    :tb_brl, :meta_brl,
                    NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    team_bruto_total=VALUES(team_bruto_total), team_liquido_total=VALUES(team_liquido_total),
                    company_cash_usd=VALUES(company_cash_usd), sum_rateado_usd=VALUES(sum_rateado_usd), sum_commissions_usd=VALUES(sum_commissions_usd),
                    company_cash_brl=VALUES(company_cash_brl), sum_rateado_brl=VALUES(sum_rateado_brl), sum_commissions_brl=VALUES(sum_commissions_brl),
                    usd_rate=VALUES(usd_rate), team_cost_settings_rate=VALUES(team_cost_settings_rate), team_cost_total=VALUES(team_cost_total),
                    equal_cost_share_per_active_seller=VALUES(equal_cost_share_per_active_seller), explicit_cost_share_per_non_trainee=VALUES(explicit_cost_share_per_non_trainee),
                    team_cost_fixed_usd=VALUES(team_cost_fixed_usd), team_cost_percent_rate=VALUES(team_cost_percent_rate), team_cost_percent_total=VALUES(team_cost_percent_total),
                    team_remaining_cost_to_cover=VALUES(team_remaining_cost_to_cover), apply_bonus=VALUES(apply_bonus), active_count=VALUES(active_count), active_cost_split_count=VALUES(active_cost_split_count), non_trainee_active_count=VALUES(non_trainee_active_count), bonus_rate=VALUES(bonus_rate),
                    team_bruto_total_brl=VALUES(team_bruto_total_brl), meta_equipe_brl=VALUES(meta_equipe_brl),
                    updated_at=NOW()';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':periodo' => $ym,
            ':tb' => (float)($team['team_bruto_total'] ?? 0),
            ':tl' => (float)($team['team_liquido_total'] ?? 0),
            ':ccu' => (float)($team['company_cash_usd'] ?? 0),
            ':sru' => (float)($team['sum_rateado_usd'] ?? 0),
            ':scu' => (float)($team['sum_commissions_usd'] ?? 0),
            ':ccb' => (float)($team['company_cash_brl'] ?? 0),
            ':srb' => (float)($team['sum_rateado_brl'] ?? 0),
            ':scb' => (float)($team['sum_commissions_brl'] ?? 0),
            ':usd_rate' => (float)($team['usd_rate'] ?? 0),
            ':cost_rate' => (float)($team['team_cost_settings_rate'] ?? 0),
            ':team_cost_total' => (float)($team['team_cost_total'] ?? 0),
            ':equal_share' => (float)($team['equal_cost_share_per_active_seller'] ?? 0),
            ':explicit_share' => (float)($team['explicit_cost_share_per_non_trainee'] ?? 0),
            ':cost_fixed' => (float)($team['team_cost_fixed_usd'] ?? 0),
            ':cost_percent_rate' => (float)($team['team_cost_percent_rate'] ?? 0),
            ':cost_percent_total' => (float)($team['team_cost_percent_total'] ?? 0),
            ':remaining_to_cover' => (float)($team['team_remaining_cost_to_cover'] ?? 0),
            ':apply_bonus' => (int)(($team['apply_bonus'] ?? false) ? 1 : 0),
            ':active_count' => (int)($team['active_count'] ?? 0),
            ':active_cost_split_count' => (int)($team['active_cost_split_count'] ?? 0),
            ':non_trainee_active_count' => (int)($team['non_trainee_active_count'] ?? 0),
            ':bonus_rate' => (float)($team['bonus_rate'] ?? 0),
            ':tb_brl' => (float)($team['team_bruto_total_brl'] ?? 0),
            ':meta_brl' => (float)($team['meta_equipe_brl'] ?? 0),
        ]);
    }

    /** Load persisted monthly summary for a given period. */
    public function loadMonthlySummary(string $ym): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM comissoes_resumo WHERE periodo = :p LIMIT 1');
        $stmt->execute([':p' => $ym]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Load persisted monthly commissions for admin page using snapshot (no join). */
    public function loadMonthly(string $ym): array
    {
        $stmt = $this->db->prepare('SELECT * FROM comissoes WHERE periodo = :p ORDER BY comissao_final DESC');
        $stmt->execute([':p' => $ym]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // Map snapshot fields to keep compatibility with views expecting name and user subarray
        foreach ($rows as &$r) {
            $r['name'] = $r['vendedor_name'] ?? ($r['name'] ?? null);
            $r['user'] = [
                'name' => $r['vendedor_name'] ?? null,
                'email' => $r['vendedor_email'] ?? null,
                'role' => $r['vendedor_role'] ?? null,
                'ativo' => isset($r['vendedor_ativo']) ? (int)$r['vendedor_ativo'] : null,
            ];
        }
        unset($r);
        return $rows;
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

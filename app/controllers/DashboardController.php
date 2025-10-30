<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Report;
use Models\Commission;
use Models\Setting;
use Models\User;
use Models\Cost;
use Models\Attendance;
use Models\Notification;

class DashboardController extends Controller
{
    public function index()
    {
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        // Período corrente (configurável; default 10->9)
        [$from, $to] = $setting->currentPeriod();
        $me = Auth::user();
        $role = $me['role'] ?? 'seller';
        $sellerId = (in_array($role, ['seller','trainee','manager'], true)) ? (int)($me['id'] ?? 0) : null;

        // Totais do período via Report (para contagem e lista) e comissões via Commission (para alinhamento de valores)
        $report = new Report();
        $totalCount = $report->countInPeriodAll($from, $to, $sellerId);

        // Comissão e totais alinhados à tela de comissões
        $comm = new Commission();
        $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        // Derivar bruto e líquido (após custos) para os cards a partir do mesmo cálculo
        $sumBruto = 0.0; $sumLiquido = 0.0; $commissionTotalUSD = 0.0;
        if (in_array($role, ['seller','trainee','manager'], true)) {
            $mineFound = false;
            foreach (($calc['items'] ?? []) as $it) {
                if ((int)($it['vendedor_id'] ?? 0) === (int)$sellerId) {
                    $sumBruto = (float)($it['bruto_total'] ?? 0);
                    $sumLiquido = (float)($it['liquido_apurado'] ?? ($it['liquido_total'] ?? 0));
                    $commissionTotalUSD = (float)($it['comissao_final'] ?? 0);
                    $mineFound = true;
                    break;
                }
            }
            if (!$mineFound && $sellerId) {
                // Fallback: somar pelas fontes no mesmo período
                try {
                    $src = $comm->sellerSourceSums((int)$sellerId, $from.' 00:00:00', $to.' 23:59:59');
                    $sumBruto = (float)($src['total']['bruto_total'] ?? 0.0);
                    $sumLiquido = (float)($src['total']['liquido_total'] ?? 0.0);
                } catch (\Throwable $e) { /* ignore */ }
            }
        } else {
            foreach (($calc['items'] ?? []) as $it) {
                $sumBruto += (float)($it['bruto_total'] ?? 0);
                $sumLiquido += (float)($it['liquido_apurado'] ?? ($it['liquido_total'] ?? 0));
                $commissionTotalUSD += (float)($it['comissao_final'] ?? 0);
            }
        }

        $summary = ['total_bruto_usd' => $sumBruto, 'total_liquido_usd' => $sumLiquido];

        // Últimas vendas do dia (filtra por vendedor quando for seller)
        $recentToday = $report->recentTodayAll(10, $sellerId);

        // Notificações recentes (somente não lidas) para o usuário logado
        $notifModel = new Notification();
        $notificationsRecent = $notifModel->listUnreadForUser((int)($me['id'] ?? 0), 5, 0);
        $notificationsUnread = $notifModel->unreadCount((int)($me['id'] ?? 0));

        // Dados adicionais para ADMIN
        $adminData = [];
        if ($role === 'admin') {
            // Totais do Financeiro (alinhados com Commission::computeRange)
            $comm = new Commission();
            $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
            $team = $calc['team'] ?? [];
            $teamBruto = (float)($team['team_bruto_total'] ?? 0);
            $companyCashUsd = (float)($team['company_cash_usd'] ?? 0);
            $companyCashBrl = (float)($team['company_cash_brl'] ?? ($companyCashUsd * $rate));
            $costRate = (float)$setting->get('cost_rate', '0.15');
            if ($costRate < 0) $costRate = 0; if ($costRate > 1) $costRate = 1;
            // Pro-Labore (% somado no período)
            $costModel = new Cost();
            $prolaborePct = (float)$costModel->sumProLaborePercentInPeriod($from, $to); // e.g. 25 => 25%
            $prolaboreUsd = $teamBruto * ($prolaborePct / 100.0);
            // Vendedores ativos (no sistema)
            $userModel = new User();
            $activeUsers = array_filter($userModel->allBasic(), function($u) {
                $r = (string)($u['role'] ?? '');
                return (int)($u['ativo'] ?? 0) === 1 && $r !== 'admin';
            });
            $activeCount = count($activeUsers);
            // Totais de comissões a pagar (USD)
            $sumCommissions = (float)($team['sum_commissions_usd'] ?? 0);
            // Por vendedor
            $report = new Report();
            $bySeller = $report->bySeller($from, $to); // retorna atendimentos e total_bruto_usd por vendedor
            // Pie: contagem de vendas por vendedor
            $pieLabels = []; $pieData = [];
            foreach ($bySeller as $row) { $pieLabels[] = (string)($row['name'] ?? ''); $pieData[] = (int)($row['atendimentos'] ?? 0); }
            // Line (por vendedor): valor vendido (USD) por vendedor (categoria = vendedor)
            $lineLabels = $pieLabels; $lineData = [];
            foreach ($bySeller as $row) { $lineData[] = (float)($row['total_bruto_usd'] ?? 0); }
            // Custos (barras): Impostos e itens explícitos (evitar duplicar Pro-Labore)
            $costsInRange = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');
            $explicit = $costsInRange['explicit_costs'] ?? [];
            $barLabels = ['Impostos'];
            $barData = [ $teamBruto * $costRate ];
            foreach ($explicit as $c) {
                $label = (string)($c['descricao'] ?? ($c['categoria'] ?? 'Custo'));
                $tipo = (string)($c['valor_tipo'] ?? 'fixed');
                if ($tipo === '') $tipo = 'fixed';
                if ($tipo === 'percent') {
                    $pct = (float)($c['valor_percent'] ?? 0);
                    $val = $teamBruto * ($pct/100.0);
                } else {
                    $val = (float)($c['valor_usd'] ?? 0);
                }
                $barLabels[] = $label; $barData[] = $val;
            }
            // Scatter: vendas (contagem) vs atendimentos realizados (Attendance) por vendedor
            // Atendimentos realizados
            $attModel = new Attendance();
            $attRows = $attModel->listRange($from, $to, null);
            $attMap = [];
            foreach ($attRows as $r) {
                $uid = (int)($r['usuario_id'] ?? 0);
                if (!$uid) continue;
                if (!isset($attMap[$uid])) $attMap[$uid] = 0;
                $attMap[$uid] += (int)($r['total_concluidos'] ?? 0);
            }
            $scatter = [];
            foreach ($bySeller as $row) {
                $uid = (int)($row['usuario_id'] ?? 0);
                $salesCount = (int)($row['atendimentos'] ?? 0);
                $attDone = (int)($attMap[$uid] ?? 0);
                $scatter[] = ['label' => (string)($row['name'] ?? ''), 'x' => $salesCount, 'y' => $attDone];
            }
            $adminData = [
                'admin_kpis' => [
                    'team_bruto_total' => $teamBruto,
                    'orders_count' => $report->countInPeriodAll($from, $to, null),
                    'global_cost_rate' => $costRate,
                    'prolabore_pct' => $prolaborePct,
                    'prolabore_usd' => $prolaboreUsd,
                    'company_cash_usd' => $companyCashUsd,
                    'company_cash_brl' => $companyCashBrl,
                    'active_sellers' => $activeCount,
                    'sum_commissions_usd' => $sumCommissions,
                ],
                'charts' => [
                    'pie' => ['labels' => $pieLabels, 'data' => $pieData],
                    'line' => ['labels' => $lineLabels, 'data' => $lineData],
                    'bar' => ['labels' => $barLabels, 'data' => $barData],
                    'scatter' => $scatter,
                ],
            ];
        }

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'rate' => $rate,
            'period_from' => $from,
            'period_to' => $to,
            'summary' => $summary,
            'total_count' => $totalCount,
            'commission_total_usd' => $commissionTotalUSD,
            'recent_today' => $recentToday,
            'notifications_recent' => $notificationsRecent,
            'notifications_unread' => $notificationsUnread,
            'admin_data' => $adminData,
        ]);
    }

    public function simulator()
    {
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        [$from, $to] = $setting->currentPeriod();
        $me = Auth::user();
        $role = $me['role'] ?? 'seller';
        if ($role !== 'admin') { return $this->redirect('/admin/dashboard'); }

        $comm = new Commission();
        $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        $team = $calc['team'] ?? [];
        $teamBruto = (float)($team['team_bruto_total'] ?? 0);
        $companyCashUsdBase = (float)($team['company_cash_usd'] ?? 0);
        $companyCashBrlBase = (float)($team['company_cash_brl'] ?? ($companyCashUsdBase * $rate));
        $costRateBase = (float)$setting->get('cost_rate', '0.15');
        if ($costRateBase < 0) $costRateBase = 0; if ($costRateBase > 1) $costRateBase = 1;
        $costModel = new Cost();
        $prolaborePctBase = (float)$costModel->sumProLaborePercentInPeriod($from, $to);
        $prolaboreUsdBase = $teamBruto * ($prolaborePctBase / 100.0);

        // Sales charts reuse real data
        $report = new Report();
        $bySeller = $report->bySeller($from, $to);
        $pieLabels = []; $pieData = []; $lineLabels = []; $lineData = [];
        foreach ($bySeller as $row) { $pieLabels[] = (string)($row['name'] ?? ''); $pieData[] = (int)($row['atendimentos'] ?? 0); $lineLabels[] = end($pieLabels); $lineData[] = (float)($row['total_bruto_usd'] ?? 0); }

        // Base explicit costs list
        $costsInRange = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');
        $explicit = array_values($costsInRange['explicit_costs'] ?? []);

        // Overrides from POST/GET
        $parseNumber = function($val): float {
            if (is_null($val)) return 0.0;
            if (is_float($val) || is_int($val)) return (float)$val;
            $s = trim((string)$val);
            if ($s === '') return 0.0;
            // Normalize BR format: 1.234,56 -> 1234.56
            if (strpos($s, ',') !== false) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // Remove thousand separators like 1,234.56
                $s = str_replace(',', '', $s);
            }
            return (float)$s;
        };

        $sim = $_POST['sim'] ?? $_GET['sim'] ?? [];
        $costRatePct = isset($sim['cost_rate_pct']) ? $parseNumber($sim['cost_rate_pct']) : ($costRateBase * 100.0);
        $costRateSim = max(0.0, min(100.0, $costRatePct)) / 100.0;
        $costRateRemoved = (int)($sim['cost_rate_remove'] ?? 0) === 1;
        if ($costRateRemoved) { $costRateSim = 0.0; }

        $barLabels = ['Impostos'];
        $barDataBase = [ $teamBruto * $costRateBase ];
        $barDataSim  = [ $teamBruto * $costRateSim ];

        $explicitBaseSum = 0.0; $explicitSimSum = 0.0;
        $simExp = $sim['explicit'] ?? [];
        // Track Pro-Labore value from explicit overrides if present
        $prolaboreUsdSim = null;
        foreach ($explicit as $idx => $c) {
            $label = (string)($c['descricao'] ?? ($c['categoria'] ?? 'Custo'));
            $tipo = (string)($c['valor_tipo'] ?? 'fixed'); if ($tipo === '') $tipo = 'fixed';
            // Base value
            if ($tipo === 'percent') { $pct = (float)($c['valor_percent'] ?? 0); $valBase = $teamBruto * ($pct/100.0); }
            else { $valBase = (float)($c['valor_usd'] ?? 0); }
            // Sim override
            $ov = $simExp[$idx] ?? [];
            // Skip if removed
            if (isset($ov['remove']) && (int)$ov['remove'] === 1) {
                // still count base for baseline, but do not add to simulated
                $barLabels[] = $label; $barDataBase[] = $valBase; $barDataSim[] = 0.0; // show removed as zero
                $explicitBaseSum += $valBase; // base sum includes it
                // If this removed item is Pro-Labore, reflect as zero in KPI
                $labelLower = strtolower($label);
                if (str_contains($labelLower, 'pro-labore') || str_contains($labelLower, 'prolabore') || str_contains($labelLower, 'pro labore')) {
                    $prolaboreUsdSim = 0.0;
                }
                // no add to explicitSimSum
                continue;
            }
            $tipoSim = $ov['valor_tipo'] ?? $tipo;
            $tipoSim = in_array($tipoSim, ['percent','fixed','fixed_brl'], true) ? $tipoSim : $tipo;
            if ($tipoSim === 'percent') {
                $pctSim = isset($ov['valor']) ? $parseNumber($ov['valor']) : (float)($c['valor_percent'] ?? 0);
                $valSim = $teamBruto * ($pctSim/100.0);
            } elseif ($tipoSim === 'fixed_brl') {
                $brl = isset($ov['valor']) ? $parseNumber($ov['valor']) : 0.0;
                $valSim = ($rate > 0) ? ($brl / $rate) : 0.0;
            } else {
                $valSim = isset($ov['valor']) ? $parseNumber($ov['valor']) : (float)($c['valor_usd'] ?? 0);
            }

            $barLabels[] = $label; $barDataBase[] = $valBase; $barDataSim[] = $valSim;
            $explicitBaseSum += $valBase; $explicitSimSum += $valSim;

            // If this cost is Pro-Labore, use its simulated value for KPI
            $labelLower = strtolower($label);
            if ($prolaboreUsdSim === null && (str_contains($labelLower, 'pro-labore') || str_contains($labelLower, 'prolabore') || str_contains($labelLower, 'pro labore'))) {
                $prolaboreUsdSim = $valSim;
            }
        }

        // Pro-Labore simulated: fallback to base if not found among explicit
        if ($prolaboreUsdSim === null) { $prolaboreUsdSim = $prolaboreUsdBase; }

        // Add new custom explicit costs (non-persistent)
        $simAdd = $sim['add'] ?? [];
        $addedOut = [];
        if (is_array($simAdd)) {
            foreach ($simAdd as $row) {
                $desc = trim((string)($row['descricao'] ?? ''));
                if ($desc === '') continue;
                $tipoN = (string)($row['valor_tipo'] ?? 'fixed');
                if (!in_array($tipoN, ['percent','fixed','fixed_brl'], true)) { $tipoN = 'fixed'; }
                $valN = $parseNumber($row['valor'] ?? 0);
                // If user marked as removed, keep it in the form (checked), but exclude from simulated totals/charts
                if (isset($row['remove']) && (int)$row['remove'] === 1) {
                    $addedOut[] = ['descricao'=>$desc,'valor_tipo'=>$tipoN,'valor'=>$valN,'remove'=>1];
                    continue;
                }
                $valBaseN = 0.0; // base has no such cost
                if ($tipoN === 'percent') { $valSimN = $teamBruto * ($valN/100.0); }
                elseif ($tipoN === 'fixed_brl') { $valSimN = ($rate > 0) ? ($valN / $rate) : 0.0; }
                else { $valSimN = $valN; }
                $barLabels[] = $desc; $barDataBase[] = $valBaseN; $barDataSim[] = $valSimN;
                $explicitSimSum += $valSimN;
                $dl = strtolower($desc);
                if ($prolaboreUsdSim === null && (str_contains($dl,'pro-labore')||str_contains($dl,'prolabore')||str_contains($dl,'pro labore'))) {
                    $prolaboreUsdSim = $valSimN;
                }
                $addedOut[] = ['descricao'=>$desc,'valor_tipo'=>$tipoN,'valor'=>$valN];
            }
        }

        // Recompute commissions dynamically based on simulated total team cost
        // (Moved after totals are computed so we can include Pro-labore in cost split)
        // 1) Aggregate sales per user
        $agg = $comm->aggregateByUser($from.' 00:00:00', $to.' 23:59:59');
        // 2) Count eligible active for cost split
        $activeCostSplit = 0;
        foreach ($agg as $row) {
            $roleU = $row['user']['role'] ?? '';
            if ((int)($row['user']['ativo'] ?? 0) === 1 && in_array($roleU, ['seller','trainee','manager'], true)) {
                $activeCostSplit++;
            }
        }
        // 3) Build simulated team cost total including impostos + pro-labore + explícitos
        $teamCostSim = ($teamBruto * $costRateSim) + $prolaboreUsdSim + $explicitSimSum;
        $equalCostShare = ($activeCostSplit > 0) ? ($teamCostSim / $activeCostSplit) : 0.0;
        // 4) Recompute commissions similar to computeRange
        try { $setRate = new Setting(); } catch (\Throwable $e) { $setRate = null; }
        $usdRate = $setRate ? (float)$setRate->get('usd_rate', '5.83') : 5.83;
        if ($usdRate <= 0) { $usdRate = 5.83; }
        $teamBrutoBRL = $teamBruto * $usdRate; $metaEquipeBRL = 50000.0 * $usdRate;
        $applyBonus = ($teamBrutoBRL >= $metaEquipeBRL);
        $activeBonusCount = 0; foreach ($agg as $row){ $r=$row['user']['role'] ?? 'seller'; if ((int)($row['user']['ativo'] ?? 0)===1 && in_array($r,['seller','trainee','manager'],true)) $activeBonusCount++; }
        $bonusRate = ($applyBonus && $activeBonusCount>0) ? (0.05 / $activeBonusCount) : 0.0;
        $sumRateadoUsd = 0.0; $sumCommissionsUsd = 0.0;
        foreach ($agg as $uid => $row) {
            $liquido = (float)($row['liquido_total'] ?? 0);
            $bruto = (float)($row['bruto_total'] ?? 0);
            $roleU = $row['user']['role'] ?? '';
            $isEligible = in_array($roleU, ['seller','trainee','manager'], true) && ((int)($row['user']['ativo'] ?? 0) === 1);
            $allocatedCost = $isEligible ? $equalCostShare : 0.0;
            $liquidoAfterCost = $liquido - $allocatedCost;
            $bruto_brl = $bruto * $usdRate;
            $liq_ap_brl = $liquidoAfterCost * $usdRate;
            if ($bruto_brl <= 30000.0 * $usdRate) { $perc = 0.15; }
            elseif ($bruto_brl <= 45000.0 * $usdRate) { $perc = 0.25; }
            else { $perc = 0.25; }
            $baseComBRL = max(0.0, $liq_ap_brl);
            $indBRL = $baseComBRL * $perc;
            $bonusBRL = $applyBonus ? ($baseComBRL * $bonusRate) : 0.0;
            $finalBRL = max(0.0, $indBRL + $bonusBRL);
            $finalUSD = ($usdRate>0) ? ($finalBRL/$usdRate) : 0.0;
            $sumRateadoUsd += $liquidoAfterCost;
            $sumCommissionsUsd += $finalUSD;
        }
        $companyCashUsdSim = $sumRateadoUsd - $sumCommissionsUsd;
        $companyCashBrlSim = $companyCashUsdSim * $usdRate;
        // If no overrides at all, mirror base values to match the rest of the system
        if (!$hasOverrides) {
            $sumCommissionsUsd = (float)($team['sum_commissions_usd'] ?? 0.0);
            $companyCashUsdSim = (float)($companyCashUsdBase ?? 0.0);
            $companyCashBrlSim = $companyCashUsdSim * $usdRate;
        }

        // Totals (base x simulated)
        $impostosBase = $teamBruto * $costRateBase;
        $impostosSim = $teamBruto * $costRateSim;
        $totalBase = $impostosBase + $prolaboreUsdBase + $explicitBaseSum;
        $totalSim = $impostosSim + $prolaboreUsdSim + $explicitSimSum;

        $adminData = [
            'admin_kpis' => [
                'team_bruto_total' => $teamBruto,
                'orders_count' => $report->countInPeriodAll($from, $to, null),
                'global_cost_rate' => $costRateSim,
                'prolabore_usd' => $prolaboreUsdSim,
                'company_cash_usd' => $companyCashUsdSim,
                'company_cash_brl' => $companyCashBrlSim,
                'active_sellers' => count($bySeller),
                'sum_commissions_usd' => (float)$sumCommissionsUsd,
            ],
            'charts' => [
                'pie' => ['labels' => $pieLabels, 'data' => $pieData],
                'line' => ['labels' => $lineLabels, 'data' => $lineData],
                'bar' => ['labels' => $barLabels, 'data' => $barDataSim],
                'bar_base' => ['labels' => $barLabels, 'data' => $barDataBase],
                'scatter' => [],
            ],
            'sim' => [
                'cost_rate_pct' => $costRateSim*100.0,
                'cost_rate_remove' => $costRateRemoved ? 1 : 0,
                'explicit' => $simExp,
                'explicit_source' => $explicit,
                'add_existing' => $addedOut,
            ],
            'totals' => [
                'impostos_base' => $impostosBase,
                'impostos_sim' => $impostosSim,
                'prolabore_base' => $prolaboreUsdBase,
                'prolabore_sim' => $prolaboreUsdSim,
                'explicit_base' => $explicitBaseSum,
                'explicit_sim' => $explicitSimSum,
                'total_base' => $totalBase,
                'total_sim' => $totalSim,
            ],
        ];

        $this->render('dashboard/simulator', [
            'title' => 'Simulador de Custos',
            'rate' => $rate,
            'period_from' => $from,
            'period_to' => $to,
            'admin_data' => $adminData,
        ]);
    }
}

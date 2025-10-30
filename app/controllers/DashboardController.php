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
            // Custos (barras): global (settings), Pro-Labore e itens explícitos
            $costsInRange = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');
            $explicit = $costsInRange['explicit_costs'] ?? [];
            $barLabels = ['Global (settings)', 'Pro-Labore'];
            $barData = [ $teamBruto * $costRate, $prolaboreUsd ];
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
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Commission;
use Models\Setting;

class CommissionsController extends Controller
{
    // Admin dashboard of commissions with period filters
    public function index()
    {
        $this->requireRole(['admin']);
        $period = trim($_GET['period'] ?? Commission::defaultPeriod());
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        $model = new Commission();
        // Retrieve USD->BRL rate for view conversions when monthly rows don't include BRL fields
        try { $setRate = new Setting(); } catch (\Throwable $e) { $setRate = null; }
        $usdRate = $setRate ? (float)$setRate->get('usd_rate', '5.83') : 5.83;
        if ($usdRate <= 0) { $usdRate = 5.83; }
        if ($from && $to) {
            $rangeFrom = $from . ' 00:00:00';
            $rangeTo = $to . ' 23:59:59';
            $calc = $model->computeRange($rangeFrom, $rangeTo);
            $items = $calc['items'];
            $team = $calc['team'];
        } else {
            [$rangeFrom, $rangeTo] = $model->monthRange($period);
            $isCurrent = ($period === Commission::defaultPeriod());
            if ($isCurrent) {
                // Current period: allow recalc and compute live
                $model->recalcMonthly($period);
                $calc = $model->computeRange($rangeFrom, $rangeTo);
                $items = $calc['items'];
                $team = $calc['team'];
            } else {
                // Past period: load frozen items and team summary only (never recompute to avoid history drift)
                $items = $model->loadMonthly($period);
                $summary = $model->loadMonthlySummary($period) ?? null;
                if ($summary) {
                    $team = [
                        'team_bruto_total' => (float)($summary['team_bruto_total'] ?? 0),
                        'team_liquido_total' => (float)($summary['team_liquido_total'] ?? 0),
                        'sum_commissions_usd' => (float)($summary['sum_commissions_usd'] ?? 0),
                        'sum_rateado_usd' => (float)($summary['sum_rateado_usd'] ?? 0),
                        'company_cash_usd' => (float)($summary['company_cash_usd'] ?? 0),
                        'company_cash_brl' => (float)($summary['company_cash_brl'] ?? 0),
                        'sum_rateado_brl' => (float)($summary['sum_rateado_brl'] ?? 0),
                        'sum_commissions_brl' => (float)($summary['sum_commissions_brl'] ?? 0),
                        'team_cost_settings_rate' => (float)($summary['team_cost_settings_rate'] ?? 0),
                        'usd_rate' => (float)($summary['usd_rate'] ?? 0),
                    ];
                } else {
                    // Derive safe metrics from persisted snapshots (no live recompute)
                    $sumBruto = 0.0; $sumLiq = 0.0; $sumCom = 0.0; $sumAllocated = 0.0; $sumLiqAp = 0.0; $hasAllocated=false; $hasLiqAp=false;
                    foreach ($items as $it) {
                        $sumBruto += (float)($it['bruto_total'] ?? 0);
                        $sumLiq += (float)($it['liquido_total'] ?? 0);
                        $sumCom += (float)($it['comissao_final'] ?? 0);
                        if (isset($it['allocated_cost'])) { $sumAllocated += (float)$it['allocated_cost']; $hasAllocated=true; }
                        if (isset($it['liquido_apurado'])) { $sumLiqAp += (float)$it['liquido_apurado']; $hasLiqAp=true; }
                        elseif (isset($it['liquido_total']) && isset($it['allocated_cost'])) { $sumLiqAp += (float)$it['liquido_total'] - (float)$it['allocated_cost']; $hasLiqAp=true; }
                    }
                    $sumRateado = $hasLiqAp ? $sumLiqAp : null;
                    $companyCash = ($sumRateado !== null) ? ($sumRateado - $sumCom) : null;
                    $team = [
                        'team_bruto_total' => $sumBruto,
                        'team_liquido_total' => $sumLiq,
                        'sum_commissions_usd' => $sumCom,
                        'sum_rateado_usd' => $sumRateado,
                        'company_cash_usd' => $companyCash,
                        'team_cost_total' => $hasAllocated ? $sumAllocated : null,
                    ];
                }
            }
        }

        // Build chart datasets (bar for individual final commissions)
        $chartLabels = []; $chartData = [];
        foreach ($items as $it) {
            $chartLabels[] = $it['name'] ?? ($it['user']['name'] ?? ('ID '.$it['vendedor_id']));
            $chartData[] = (float)($it['comissao_final'] ?? 0);
        }

        // History for line chart: last 6 months totals (sum of comissao_final)
        $historyLabels = []; $historyTotals = [];
        for ($i=5; $i>=0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $rows = $model->loadMonthly($ym);
            $sum = 0.0; foreach ($rows as $r) { $sum += (float)($r['comissao_final'] ?? 0); }
            $historyLabels[] = $ym; $historyTotals[] = round($sum,2);
        }

        $this->render('commissions/admin', [
            'title' => 'Comissões (Admin)',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'items' => $items,
            'team' => $team ?? null,
            'goal' => Commission::TEAM_GOAL_USD,
            'usdRate' => $usdRate,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'historyLabels' => $historyLabels,
            'historyTotals' => $historyTotals,
        ]);
    }

    // POST: recalc monthly commissions and persist
    public function recalc()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $period = trim($_POST['period'] ?? Commission::defaultPeriod());
        // Only allow recalc for current period
        if ($period === Commission::defaultPeriod()) {
            (new Commission())->recalcMonthly($period);
        }
        $this->redirect('/admin/commissions?period='.$period);
    }

    // POST: freeze a past period by persisting snapshots and summary
    public function freeze()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $period = trim($_POST['period'] ?? '');
        if ($period === '') { $period = Commission::defaultPeriod(); }
        $isCurrent = ($period === Commission::defaultPeriod());
        // Do not allow freezing the current rolling period
        if ($isCurrent) {
            return $this->redirect('/admin/commissions?period='.$period);
        }
        $m = new Commission();
        [$from,$to] = $m->monthRange($period);
        // Compute once and persist full snapshot and team summary
        $calc = $m->computeRange($from, $to);
        $m->persistMonthly($period, $calc['items'] ?? []);
        $m->persistMonthlySummary($period, $calc['team'] ?? []);
        return $this->redirect('/admin/commissions?period='.$period);
    }

    // Seller-only page: show own commissions and history
    public function me()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $u = Auth::user();
        $period = trim($_GET['period'] ?? Commission::defaultPeriod());
        $model = new Commission();
        [$from, $to] = $model->monthRange($period);
        $calc = $model->computeRange($from, $to);

        $mine = null; $team = $calc['team'];
        foreach ($calc['items'] as $it) {
            if ((int)$it['vendedor_id'] === (int)($u['id'] ?? 0)) { $mine = $it; break; }
        }

        $history = $model->historyForSeller((int)($u['id'] ?? 0), 12);
        $labels = []; $values = [];
        foreach ($history as $h) { $labels[] = $h['periodo']; $values[] = (float)$h['comissao_final']; }

        $this->render('commissions/seller', [
            'title' => 'Minhas Comissões',
            'period' => $period,
            'mine' => $mine,
            'team' => $team,
            'goal' => Commission::TEAM_GOAL_USD,
            'historyLabels' => $labels,
            'historyValues' => $values,
        ]);
    }

    public function exportCsv()
    {
        $this->requireRole(['admin']);
        $period = trim($_GET['period'] ?? Commission::defaultPeriod());
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $model = new Commission();
        if ($from && $to) {
            $calc = $model->computeRange($from . ' 00:00:00', $to . ' 23:59:59');
            $items = $calc['items'];
        } else {
            $items = $model->loadMonthly($period);
            if (!$items) {
                [$rf,$rt] = $model->monthRange($period);
                $items = $model->computeRange($rf, $rt)['items'];
            }
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="comissoes_'. ($from && $to ? ($from.'_'. $to) : $period) .'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['vendedor_id','nome','ativo','bruto_total','liquido_total','comissao_individual','bonus','comissao_final']);
        foreach ($items as $it) {
            $name = $it['name'] ?? ($it['user']['name'] ?? '');
            $ativo = (int)($it['ativo'] ?? ($it['user']['ativo'] ?? 0));
            fputcsv($out, [
                $it['vendedor_id'] ?? null,
                $name,
                $ativo,
                $it['bruto_total'] ?? 0,
                $it['liquido_total'] ?? 0,
                $it['comissao_individual'] ?? 0,
                $it['bonus'] ?? 0,
                $it['comissao_final'] ?? 0,
            ]);
        }
        fclose($out);
        exit;
    }

    // Debug page: show raw variables and costs breakdown for the logged-in user
    public function debug()
    {
        $this->requireRole(['seller','manager','admin']);
        // Password protection via settings
        try { $set = new \Models\Setting(); } catch (\Throwable $e) { $set = null; }
        $pwd = $set ? (string)$set->get('commissions_debug_password', '') : '';
        if ($pwd !== '') {
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            $ok = isset($_SESSION['comm_dbg_ok']) && $_SESSION['comm_dbg_ok'] === true;
            if (!$ok) {
                $try = isset($_GET['pwd']) ? (string)$_GET['pwd'] : '';
                if ($try !== '' && hash_equals($pwd, $try)) {
                    $_SESSION['comm_dbg_ok'] = true;
                    header('Location: /admin/commissions/debug');
                    exit;
                }
                // Render password prompt
                return $this->render('commissions/debug_password', [
                    'title' => 'Debug de Comissões (Protegido)',
                    'error' => ($try !== '' && $try !== $pwd) ? 'Senha incorreta' : null,
                ]);
            }
        }
        $u = Auth::user();
        $period = trim($_GET['period'] ?? Commission::defaultPeriod());
        $model = new Commission();
        [$from, $to] = $model->monthRange($period);
        $calc = $model->computeRange($from, $to);
        $mine = null; $team = $calc['team'];
        foreach ($calc['items'] as $it) {
            if ((int)$it['vendedor_id'] === (int)($u['id'] ?? 0)) { $mine = $it; break; }
        }
        $costs = $model->costsInRange($from, $to);
        $sources = $model->sellerSourceSums((int)($u['id'] ?? 0), $from, $to);
        $this->render('commissions/debug', [
            'title' => 'Debug de Comissões',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'mine' => $mine,
            'team' => $team,
            'costs' => $costs,
            'sources' => $sources,
            'items' => $calc['items'] ?? [],
        ]);
    }
}

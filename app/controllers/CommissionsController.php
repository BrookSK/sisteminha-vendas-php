<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Commission;

class CommissionsController extends Controller
{
    // Admin dashboard of commissions with period filters
    public function index()
    {
        $this->requireRole(['admin']);
        $period = trim($_GET['period'] ?? date('Y-m'));
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        $model = new Commission();
        if ($from && $to) {
            $rangeFrom = $from . ' 00:00:00';
            $rangeTo = $to . ' 23:59:59';
            $calc = $model->computeRange($rangeFrom, $rangeTo);
            $items = $calc['items'];
            $team = $calc['team'];
        } else {
            [$rangeFrom, $rangeTo] = $model->monthRange($period);
            // Prefer persisted summary for speed; compute team info on the fly
            $items = $model->loadMonthly($period);
            if (!$items) {
                $calc = $model->computeRange($rangeFrom, $rangeTo);
                $items = $calc['items'];
                $team = $calc['team'];
            } else {
                // Derive team data from persisted items
                $teamBruto = 0.0; $active = 0;
                foreach ($items as $it) { $teamBruto += (float)$it['bruto_total']; if ((int)($it['ativo'] ?? 0) === 1) $active++; }
                $apply = $teamBruto >= Commission::TEAM_GOAL_USD;
                $rate = ($apply && $active>0) ? (0.05/$active) : 0.0;
                $team = ['team_bruto_total'=>round($teamBruto,2), 'apply_bonus'=>$apply, 'active_count'=>$active, 'bonus_rate'=>$rate];
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
        $period = trim($_POST['period'] ?? date('Y-m'));
        (new Commission())->recalcMonthly($period);
        $this->redirect('/admin/commissions?period='.$period);
    }

    // Seller-only page: show own commissions and history
    public function me()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $u = Auth::user();
        $period = trim($_GET['period'] ?? date('Y-m'));
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
        $period = trim($_GET['period'] ?? date('Y-m'));
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
        $u = Auth::user();
        $period = trim($_GET['period'] ?? date('Y-m'));
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
        ]);
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Models\Report;
use Models\Commission;
use Models\Setting;
use Models\User;
use Models\Attendance;

class FinanceController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $costRate = (float)$setting->get('cost_rate', '0.15');

        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if (!$from || !$to) {
            // Use Settings current period (10->9 rolling) as default
            [$from, $to] = $setting->currentPeriod();
        }

        $report = new Report();
        $comm = new Commission();
        // Prefer persisted commissions for closed periods (do not recompute)
        $ymGuess = date('Y-m', strtotime($from));
        [$rangeFromGuess, $rangeToGuess] = $comm->monthRange($ymGuess);
        $isExactPeriod = ($from.' 00:00:00' === $rangeFromGuess) && ($to.' 23:59:59' === $rangeToGuess);
        $isCurrentPeriod = ($ymGuess === Commission::defaultPeriod());
        if ($isExactPeriod && !$isCurrentPeriod) {
            $items = $comm->loadMonthly($ymGuess);
            $summary = $comm->loadMonthlySummary($ymGuess) ?? null;
            // Build a commCalc structure using persisted rows and summary (no live recompute for past)
            $sumBruto = 0.0; $sumLiquido = 0.0; $sumCom = 0.0;
            foreach ($items as $it) { $sumBruto += (float)($it['bruto_total'] ?? 0); $sumLiquido += (float)($it['liquido_total'] ?? 0); $sumCom += (float)($it['comissao_final'] ?? 0); }
            if ($summary) {
                $team = [
                    'team_bruto_total' => (float)($summary['team_bruto_total'] ?? $sumBruto),
                    'team_liquido_total' => (float)($summary['team_liquido_total'] ?? $sumLiquido),
                    'sum_commissions_usd' => (float)($summary['sum_commissions_usd'] ?? $sumCom),
                    'sum_rateado_usd' => (float)($summary['sum_rateado_usd'] ?? 0),
                    'company_cash_usd' => (float)($summary['company_cash_usd'] ?? 0),
                ];
            } else {
                // Derive team metrics from persisted item snapshots (no live recompute)
                $sumAllocated = 0.0; $sumLiqAp = 0.0; $hasLiqAp = false; $hasAllocated = false;
                foreach ($items as $it) {
                    if (isset($it['allocated_cost'])) { $sumAllocated += (float)$it['allocated_cost']; $hasAllocated = true; }
                    if (isset($it['liquido_apurado'])) { $sumLiqAp += (float)$it['liquido_apurado']; $hasLiqAp = true; }
                    elseif (isset($it['liquido_total']) && isset($it['allocated_cost'])) { $sumLiqAp += (float)$it['liquido_total'] - (float)$it['allocated_cost']; $hasLiqAp = true; }
                }
                $sumRateado = $hasLiqAp ? $sumLiqAp : null;
                $teamCostTotal = $hasAllocated ? $sumAllocated : null;
                $companyCash = ($sumRateado !== null) ? ($sumRateado - $sumCom) : null;
                $team = [
                    'team_bruto_total' => $sumBruto,
                    'team_liquido_total' => $sumLiquido,
                    'sum_commissions_usd' => $sumCom,
                    'sum_rateado_usd' => $sumRateado,
                    'company_cash_usd' => $companyCash,
                    'team_cost_total' => $teamCostTotal,
                ];
            }
            $commCalc = [ 'items' => $items, 'team' => $team ];
        } else {
            $commCalc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        }
        $costs = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');

        // Attendance-specific period (defaults to main period)
        $attFrom = $_GET['att_from'] ?? $from;
        $attTo = $_GET['att_to'] ?? $to;

        // Attendance summary by user for the period and for today
        $attendanceByUser = [];
        try {
            $attModel = new Attendance();
            $rowsInRange = $attModel->listRange($attFrom, $attTo, null);
            $fromD = substr($attFrom, 0, 10);
            $toD = substr($attTo, 0, 10);
            $today = date('Y-m-d');
            foreach ($rowsInRange as $row) {
                $uid = (int)($row['usuario_id'] ?? 0);
                if ($uid <= 0) continue;
                $d = (string)($row['data'] ?? '');
                if ($d < $fromD || $d > $toD) continue;
                if (!isset($attendanceByUser[$uid])) {
                    $attendanceByUser[$uid] = [
                        'today_total' => 0,
                        'today_done' => 0,
                        'period_total' => 0,
                        'period_done' => 0,
                        'last_att_date' => null,
                        'rows' => [],
                    ];
                }
                $attendanceByUser[$uid]['rows'][] = $row;
                $attendanceByUser[$uid]['period_total'] += (int)($row['total_atendimentos'] ?? 0);
                $attendanceByUser[$uid]['period_done'] += (int)($row['total_concluidos'] ?? 0);
                if ($attendanceByUser[$uid]['last_att_date'] === null || $attendanceByUser[$uid]['last_att_date'] < $d) {
                    $attendanceByUser[$uid]['last_att_date'] = $d;
                }
                if ($d === $today) {
                    $attendanceByUser[$uid]['today_total'] += (int)($row['total_atendimentos'] ?? 0);
                    $attendanceByUser[$uid]['today_done'] += (int)($row['total_concluidos'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            // ignore attendance errors
        }

        // Group commissions by role
        $byRole = ['seller'=>0.0,'manager'=>0.0,'trainee'=>0.0,'organic'=>0.0,'admin'=>0.0];
        foreach (($commCalc['items'] ?? []) as $it) {
            $role = (string)($it['user']['role'] ?? 'seller');
            if (!isset($byRole[$role])) $byRole[$role] = 0.0;
            $byRole[$role] += (float)($it['comissao_final'] ?? 0);
        }

        // Build users list for view: for past periods, include snapshots so excluded users still show
        $usersList = (new User())->allBasic();
        if ($isExactPeriod && !$isCurrentPeriod) {
            $byId = [];
            foreach ($usersList as $u) { $byId[(int)($u['id'] ?? 0)] = $u; }
            foreach (($commCalc['items'] ?? []) as $it) {
                $vid = (int)($it['vendedor_id'] ?? 0);
                $snap = $it['user'] ?? [];
                if ($vid > 0) {
                    $byId[$vid] = [
                        'id' => $vid,
                        'name' => $snap['name'] ?? ($it['name'] ?? ('#'.$vid)),
                        'email' => $snap['email'] ?? null,
                        'role' => $snap['role'] ?? 'seller',
                        'ativo' => $snap['ativo'] ?? 0,
                    ];
                }
            }
            $usersList = array_values($byId);
        }

        $this->render('finance/index', [
            'title' => 'Financeiro',
            'rate' => $rate,
            'cost_rate' => $costRate,
            'from' => $from,
            'to' => $to,
            'comm' => $commCalc,
            'costs' => $costs,
            'byRole' => $byRole,
            'users' => $usersList,
            'attendanceByUser' => $attendanceByUser,
            'att_from' => $attFrom,
            'att_to' => $attTo,
        ]);
    }

    public function exportCompanyPdf()
    {
        $this->requireRole(['admin']);
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $costRate = (float)$setting->get('cost_rate', '0.15');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if (!$from || !$to) { [$from,$to] = $setting->currentPeriod(); }

        $comm = new Commission();
        $ymGuess = date('Y-m', strtotime($from));
        [$rangeFromGuess, $rangeToGuess] = $comm->monthRange($ymGuess);
        $isExactPeriod = ($from.' 00:00:00' === $rangeFromGuess) && ($to.' 23:59:59' === $rangeToGuess);
        $isCurrentPeriod = ($ymGuess === Commission::defaultPeriod());
        if ($isExactPeriod && !$isCurrentPeriod) {
            $items = $comm->loadMonthly($ymGuess);
            $summary = $comm->loadMonthlySummary($ymGuess) ?? null;
            $sumBruto = 0.0; $sumLiquido = 0.0; $sumCom = 0.0;
            foreach ($items as $it) { $sumBruto += (float)($it['bruto_total'] ?? 0); $sumLiquido += (float)($it['liquido_total'] ?? 0); $sumCom += (float)($it['comissao_final'] ?? 0); }
            if ($summary) {
                $team = [
                    'team_bruto_total'=>(float)($summary['team_bruto_total'] ?? $sumBruto),
                    'team_liquido_total'=>(float)($summary['team_liquido_total'] ?? $sumLiquido),
                    'sum_commissions_usd'=>(float)($summary['sum_commissions_usd'] ?? $sumCom),
                    'sum_rateado_usd'=>(float)($summary['sum_rateado_usd'] ?? 0),
                    'company_cash_usd'=>(float)($summary['company_cash_usd'] ?? 0),
                ];
            } else {
                $team = [
                    'team_bruto_total'=>$sumBruto,
                    'team_liquido_total'=>$sumLiquido,
                    'sum_commissions_usd'=>$sumCom,
                    'sum_rateado_usd'=>null,
                    'company_cash_usd'=>null,
                ];
            }
            $commCalc = [ 'items'=>$items, 'team'=>$team ];
        } else {
            $commCalc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        }
        $costs = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');

        // Render a minimal HTML using same view content
        ob_start();
        $title = 'Relatório Financeiro (Empresa)';
        $rate_ = $rate; $cost_rate_ = $costRate; $from_=$from; $to_=$to;
        $comm_ = $commCalc; $costs_ = $costs;
        include dirname(__DIR__) . '/views/finance/partials_company.php';
        $html = ob_get_clean();

        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('financeiro_empresa.pdf', ['Attachment' => true]);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="padding:16px;font-family:Arial,sans-serif">'
            .'<p><strong>Dompdf não encontrado.</strong> Instale com:</p>'
            .'<pre>composer require dompdf/dompdf</pre>'
            .'<hr>'.$html.'</div>';
    }

    public function exportCostsCsv()
    {
        $this->requireRole(['admin']);
        $setting = new Setting();
        $from = $_GET['from'] ?? null; $to = $_GET['to'] ?? null;
        if (!$from || !$to) { [$from,$to] = $setting->currentPeriod(); }
        $rate = (float)$setting->get('usd_rate', '5.83');
        $comm = new Commission();
        $ymGuess = date('Y-m', strtotime($from));
        [$rangeFromGuess, $rangeToGuess] = $comm->monthRange($ymGuess);
        $isExactPeriod = ($from.' 00:00:00' === $rangeFromGuess) && ($to.' 23:59:59' === $rangeToGuess);
        $isCurrentPeriod = ($ymGuess === Commission::defaultPeriod());
        if ($isExactPeriod && !$isCurrentPeriod) {
            $items = $comm->loadMonthly($ymGuess);
            $summary = $comm->loadMonthlySummary($ymGuess) ?? null;
            $sumBruto = 0.0; $sumLiquido = 0.0; $sumCom = 0.0;
            foreach ($items as $it) { $sumBruto += (float)($it['bruto_total'] ?? 0); $sumLiquido += (float)($it['liquido_total'] ?? 0); $sumCom += (float)($it['comissao_final'] ?? 0); }
            if ($summary) {
                $team = [
                    'team_bruto_total'=>(float)($summary['team_bruto_total'] ?? $sumBruto),
                    'team_liquido_total'=>(float)($summary['team_liquido_total'] ?? $sumLiquido),
                    'sum_commissions_usd'=>(float)($summary['sum_commissions_usd'] ?? $sumCom),
                    'sum_rateado_usd'=>(float)($summary['sum_rateado_usd'] ?? 0),
                    'company_cash_usd'=>(float)($summary['company_cash_usd'] ?? 0),
                ];
            } else {
                $team = [
                    'team_bruto_total'=>$sumBruto,
                    'team_liquido_total'=>$sumLiquido,
                    'sum_commissions_usd'=>$sumCom,
                    'sum_rateado_usd'=>null,
                    'company_cash_usd'=>null,
                ];
            }
            $calc = [ 'items'=>$items, 'team'=>$team ];
        } else {
            $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        }
        $teamBruto = (float)($calc['team']['team_bruto_total'] ?? 0);
        $costs = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="financeiro_custos_'.urlencode($from).'_'.urlencode($to).'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['descricao','tipo','valor_usd_final','valor_brl_final','formula']);
        foreach (($costs['explicit_costs'] ?? []) as $c) {
            $tipo = (string)($c['valor_tipo'] ?? 'fixed');
            if ($tipo === '') { $tipo = 'fixed'; }
            if ($tipo === 'percent') {
                $pct = (float)($c['valor_percent'] ?? 0);
                $usd = $teamBruto * ($pct/100.0);
                $formula = number_format($pct,2).'% x US$ '.number_format($teamBruto,2).' = US$ '.number_format($usd,2);
            } else {
                $usd = (float)($c['valor_usd'] ?? 0);
                $formula = '';
            }
            $brl = $usd * $rate;
            fputcsv($out, [
                (string)($c['descricao'] ?? ''),
                $tipo,
                number_format($usd, 2, '.', ''),
                number_format($brl, 2, '.', ''),
                $formula,
            ]);
        }
        fclose($out);
        exit;
    }

    public function exportAttendancesCsv()
    {
        $this->requireRole(['admin']);
        $attFrom = $_GET['att_from'] ?? ($_GET['from'] ?? null);
        $attTo = $_GET['att_to'] ?? ($_GET['to'] ?? null);
        if (!$attFrom || !$attTo) { $set = new Setting(); [$attFrom,$attTo] = $set->currentPeriod(); }
        $att = new Attendance();
        $rows = $att->listRange($attFrom, $attTo, null);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="financeiro_atendimentos_'.urlencode($attFrom).'_'.urlencode($attTo).'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['data','usuario_id','usuario_email','total_atendimentos','total_concluidos','created_at','updated_at']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['data'] ?? '',
                $r['usuario_id'] ?? '',
                $r['usuario_email'] ?? '',
                $r['total_atendimentos'] ?? 0,
                $r['total_concluidos'] ?? 0,
                $r['created_at'] ?? '',
                $r['updated_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function exportAttendancesXlsx()
    {
        $this->requireRole(['admin']);
        $attFrom = $_GET['att_from'] ?? ($_GET['from'] ?? null);
        $attTo = $_GET['att_to'] ?? ($_GET['to'] ?? null);
        if (!$attFrom || !$attTo) { $set = new Setting(); [$attFrom,$attTo] = $set->currentPeriod(); }
        $att = new Attendance();
        $rows = $att->listRange($attFrom, $attTo, null);
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Atendimentos');
            $sheet->fromArray(['Período', $attFrom.' a '.$attTo], null, 'A1');
            $sheet->fromArray(['data','usuario_id','usuario_email','total_atendimentos','total_concluidos','created_at','updated_at'], null, 'A3');
            $r = 4;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $row['data'] ?? '',
                    $row['usuario_id'] ?? '',
                    $row['usuario_email'] ?? '',
                    $row['total_atendimentos'] ?? 0,
                    $row['total_concluidos'] ?? 0,
                    $row['created_at'] ?? '',
                    $row['updated_at'] ?? '',
                ], null, 'A'.$r);
                $r++;
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="financeiro_atendimentos_'.urlencode($attFrom).'_'.urlencode($attTo).'.xlsx"');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
        // Fallback CSV
        $this->exportAttendancesCsv();
    }

    public function exportCompanyXlsx()
    {
        $this->requireRole(['admin']);
        $setting = new Setting();
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if (!$from || !$to) { [$from,$to] = $setting->currentPeriod(); }
        $comm = new Commission();
        $ymGuess = date('Y-m', strtotime($from));
        [$rangeFromGuess, $rangeToGuess] = $comm->monthRange($ymGuess);
        $isExactPeriod = ($from.' 00:00:00' === $rangeFromGuess) && ($to.' 23:59:59' === $rangeToGuess);
        $isCurrentPeriod = ($ymGuess === Commission::defaultPeriod());
        if ($isExactPeriod && !$isCurrentPeriod) {
            $items = $comm->loadMonthly($ymGuess);
            $sumBruto = 0.0; $sumLiquido = 0.0; $sumCom = 0.0;
            foreach ($items as $it) { $sumBruto += (float)($it['bruto_total'] ?? 0); $sumLiquido += (float)($it['liquido_total'] ?? 0); $sumCom += (float)($it['comissao_final'] ?? 0); }
            $calc = [ 'items'=>$items, 'team'=>['team_bruto_total'=>$sumBruto,'team_liquido_total'=>$sumLiquido,'sum_commissions_usd'=>$sumCom] ];
        } else {
            $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        }

        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Financeiro Empresa');
            $sheet->fromArray([
                ['Período', $from.' a '.$to],
                ['Company Cash (USD)', $calc['team']['company_cash_usd'] ?? 0],
                ['Bruto Equipe (USD)', $calc['team']['team_bruto_total'] ?? 0],
                ['Líquido Rateado (USD)', $calc['team']['sum_rateado_usd'] ?? 0],
                ['Comissões (USD)', $calc['team']['sum_commissions_usd'] ?? 0],
                ['Custos Totais (USD)', $calc['team']['team_cost_total'] ?? 0],
            ]);
            // Header for items
            $startRow = 8;
            $sheet->fromArray(['Vendedor','Role','Bruto USD','Líquido USD','Custo Alocado USD','Líquido Apurado USD','Comissão Final USD'], null, 'A'.$startRow);
            $r = $startRow+1;
            foreach (($calc['items'] ?? []) as $it) {
                $sheet->fromArray([
                    $it['user']['name'] ?? '',
                    $it['user']['role'] ?? '',
                    $it['bruto_total'] ?? 0,
                    $it['liquido_total'] ?? 0,
                    $it['allocated_cost'] ?? 0,
                    $it['liquido_apurado'] ?? 0,
                    $it['comissao_final'] ?? 0,
                ], null, 'A'.$r);
                $r++;
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="financeiro_empresa_'.$from.'_'.$to.'.xlsx"');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
        // Fallback CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="financeiro_empresa_'.$from.'_'.$to.'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Periodo', $from.' a '.$to]);
        fputcsv($out, ['Company Cash USD', $calc['team']['company_cash_usd'] ?? 0]);
        fputcsv($out, ['Team Bruto USD', $calc['team']['team_bruto_total'] ?? 0]);
        fputcsv($out, ['Liquido Rateado USD', $calc['team']['sum_rateado_usd'] ?? 0]);
        fputcsv($out, ['Comissoes USD', $calc['team']['sum_commissions_usd'] ?? 0]);
        fputcsv($out, ['Custos Totais USD', $calc['team']['team_cost_total'] ?? 0]);
        fputcsv($out, []);
        fputcsv($out, ['Vendedor','Role','Bruto USD','Líquido USD','Custo Alocado USD','Líquido Apurado USD','Comissão Final USD']);
        foreach (($calc['items'] ?? []) as $it) {
            fputcsv($out, [
                $it['user']['name'] ?? '',
                $it['user']['role'] ?? '',
                $it['bruto_total'] ?? 0,
                $it['liquido_total'] ?? 0,
                $it['allocated_cost'] ?? 0,
                $it['liquido_apurado'] ?? 0,
                $it['comissao_final'] ?? 0,
            ]);
        }
        fclose($out);
        exit;
    }

    public function exportSellerPdf()
    {
        $this->requireRole(['admin']);
        $sellerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
        if ($sellerId <= 0) { http_response_code(400); echo 'seller_id inválido'; return; }
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if (!$from || !$to) { [$from,$to] = $setting->currentPeriod(); }
        $comm = new Commission();
        $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        $mine = null; $team = $calc['team'];
        foreach (($calc['items'] ?? []) as $it) { if ((int)$it['vendedor_id'] === $sellerId) { $mine = $it; break; } }

        // Attendance data for this seller in range and today
        $attMine = ['today_total'=>0,'today_done'=>0,'rows'=>[]];
        try {
            $attModel = new Attendance();
            $rows = $attModel->list(1000, 0, $sellerId);
            $fromD = substr($from, 0, 10);
            $toD = substr($to, 0, 10);
            $today = date('Y-m-d');
            foreach ($rows as $r) {
                $d = (string)($r['data'] ?? '');
                if ($d < $fromD || $d > $toD) continue;
                $attMine['rows'][] = $r;
                if ($d === $today) {
                    $attMine['today_total'] += (int)($r['total_atendimentos'] ?? 0);
                    $attMine['today_done'] += (int)($r['total_concluidos'] ?? 0);
                }
            }
        } catch (\Throwable $e) {}

        ob_start();
        $title = 'Relatório de Desempenho do Vendedor';
        $rate_ = $rate; $from_=$from; $to_=$to; $team_=$team; $mine_=$mine; $att_=$attMine;
        include dirname(__DIR__) . '/views/finance/partials_seller.php';
        $html = ob_get_clean();

        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $displayName = $mine_['user']['name'] ?? ($mine['user']['name'] ?? '') ?? '';
            if ($displayName === '' && isset($mine['user']['email'])) { $displayName = $mine['user']['email']; }
            if ($displayName === '') { $displayName = 'vendedor_'.$sellerId; }
            $safe = strtolower(trim((string)$displayName));
            $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $safe);
            $filename = ($safe !== '' ? $safe : ('vendedor_'.$sellerId)).'_financeiro.pdf';
            $dompdf->stream($filename, ['Attachment' => true]);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="padding:16px;font-family:Arial,sans-serif">'
            .'<p><strong>Dompdf não encontrado.</strong> Instale com:</p>'
            .'<pre>composer require dompdf/dompdf</pre>'
            .'<hr>'.$html.'</div>';
    }
}

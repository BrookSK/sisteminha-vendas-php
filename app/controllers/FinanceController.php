<?php
namespace Controllers;

use Core\Controller;
use Models\Report;
use Models\Commission;
use Models\Setting;
use Models\User;

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
        $commCalc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        $costs = $comm->costsInRange($from.' 00:00:00', $to.' 23:59:59');

        // Group commissions by role
        $byRole = ['seller'=>0.0,'manager'=>0.0,'trainee'=>0.0,'organic'=>0.0,'admin'=>0.0];
        foreach (($commCalc['items'] ?? []) as $it) {
            $role = (string)($it['user']['role'] ?? 'seller');
            if (!isset($byRole[$role])) $byRole[$role] = 0.0;
            $byRole[$role] += (float)($it['comissao_final'] ?? 0);
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
            'users' => (new User())->allBasic(),
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
        $commCalc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
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

    public function exportCompanyXlsx()
    {
        $this->requireRole(['admin']);
        $setting = new Setting();
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if (!$from || !$to) { [$from,$to] = $setting->currentPeriod(); }
        $comm = new Commission();
        $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');

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

        ob_start();
        $title = 'Relatório de Desempenho do Vendedor';
        $rate_ = $rate; $from_=$from; $to_=$to; $team_=$team; $mine_=$mine;
        include dirname(__DIR__) . '/views/finance/partials_seller.php';
        $html = ob_get_clean();

        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('vendedor_'.$sellerId.'.pdf', ['Attachment' => true]);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="padding:16px;font-family:Arial,sans-serif">'
            .'<p><strong>Dompdf não encontrado.</strong> Instale com:</p>'
            .'<pre>composer require dompdf/dompdf</pre>'
            .'<hr>'.$html.'</div>';
    }
}

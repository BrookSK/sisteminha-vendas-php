<?php
namespace Controllers;

use Core\Controller;
use Models\Report;
use Models\Setting;
use Models\User;

class ReportsController extends Controller
{
    public function index()
    {
        $this->requireRole(['manager','admin']);
        $report = new Report();
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $costRate = (float)$setting->get('cost_rate', '0.15');

        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;

        if ($from && $to) {
            $week = $report->summary($from, $to, $sellerId);
            $month = $week; // when filtered, show the same summary in both cards
            $sellers = $report->bySeller($from, $to);
        } else {
            $week = $report->weekSummary();
            // default month = current month
            $monthFrom = date('Y-m-01');
            $monthTo = date('Y-m-t');
            $month = $report->summary($monthFrom, $monthTo, null);
            $sellers = $report->bySeller($monthFrom, $monthTo);
        }
        $months = $report->lastMonthsComparison(3);

        $this->render('reports/index', [
            'title' => 'Relatórios',
            'rate' => $rate,
            'cost_rate' => $costRate,
            'week' => $week,
            'month' => $month,
            'months' => $months,
            'sellers' => $sellers,
            'from' => $from,
            'to' => $to,
            'seller_id' => $sellerId,
            'users' => (new User())->allBasic(),
        ]);
    }

    public function exportPdf()
    {
        $this->requireRole(['manager','admin']);
        // Gera o mesmo HTML da página de relatórios e converte via Dompdf, se disponível
        $report = new Report();
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $costRate = (float)$setting->get('cost_rate', '0.15');
        // compute current month summary
        $monthFrom = date('Y-m-01');
        $monthTo = date('Y-m-t');
        $data = [
            'title' => 'Relatórios',
            'rate' => $rate,
            'cost_rate' => $costRate,
            'week' => $report->weekSummary(),
            'month' => $report->summary($monthFrom, $monthTo, null),
            'months' => $report->lastMonthsComparison(3),
            'sellers' => $report->bySeller($monthFrom, $monthTo),
        ];

        // Render HTML sem layout para PDF (opcionalmente use um layout próprio)
        ob_start();
        extract($data);
        include dirname(__DIR__) . '/views/reports/index.php';
        $html = ob_get_clean();

        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('relatorios.pdf', ['Attachment' => true]);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="padding:16px;font-family:Arial, sans-serif">'
            .'<p><strong>Dompdf não encontrado.</strong> Instale com:</p>'
            .'<pre>composer require dompdf/dompdf</pre>'
            .'<hr>'
            .$html
            .'</div>';
    }

    public function costAllocationCsv()
    {
        $this->requireRole(['manager','admin']);
        // Rateio de custos externos por venda no período [from, to]
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $report = new Report();
        $sales = $report->salesInPeriod($from, $to);
        $extCosts = $report->externalCostsInPeriod($from, $to);
        $count = max(1, count($sales));
        $perSale = $extCosts / $count;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rateio_custos_'. $from . '_'. $to .'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['venda_id','data','cliente','bruto_usd','rateio_custo_externo_usd']);
        foreach ($sales as $s) {
            fputcsv($out, [
                $s['id'],
                $s['created_at'],
                $s['cliente_nome'] ?? '-',
                $s['bruto_usd'],
                round($perSale, 2)
            ]);
        }
        fclose($out);
        exit;
    }
}

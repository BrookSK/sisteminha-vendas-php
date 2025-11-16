<?php
namespace Controllers;

use Core\Controller;
use Models\Report;
use Models\Setting;
use Models\User;
use Models\Commission;
use Models\MonthlySnapshot;

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

        // Helper to compute rolling period: day 10 of a month to day 9 of next month
        $computeRollingPeriod = function(): array {
            $today = new \DateTime('today');
            $day = (int)$today->format('d');
            if ($day >= 10) {
                $fromD = new \DateTime($today->format('Y-m-10'));
                $toD = (clone $fromD)->modify('+1 month');
                $toD->setDate((int)$toD->format('Y'), (int)$toD->format('m'), 9);
            } else {
                $toD = new \DateTime($today->format('Y-m-09'));
                $fromD = (clone $toD)->modify('-1 month');
                $fromD->setDate((int)$fromD->format('Y'), (int)$fromD->format('m'), 10);
            }
            return [$fromD->format('Y-m-d'), $toD->format('Y-m-d')];
        };

        // If no GET period, try settings; if settings empty, use rolling 10->9
        if (!$from || !$to) {
            $periodStart = (string)$setting->get('current_period_start', '');
            $periodEnd = (string)$setting->get('current_period_end', '');
            if ($periodStart !== '' && $periodEnd !== '') {
                $from = $from ?: $periodStart;
                $to = $to ?: $periodEnd;
            } else {
                [$rf,$rt] = $computeRollingPeriod();
                $from = $from ?: $rf;
                $to = $to ?: $rt;
            }
        }

        if ($from && $to) {
            $snapModel = new MonthlySnapshot();
            $companySnap = $snapModel->loadCompanyForPeriod($from, $to);
            if ($companySnap) {
                // Usar dados congelados para o resumo do mês e desempenho dos vendedores
                $week = $report->summary($from, $to, $sellerId);
                $month = [
                    'atendimentos' => (int)($companySnap['atendimentos'] ?? 0),
                    'atendimentos_concluidos' => (int)($companySnap['atendimentos_concluidos'] ?? 0),
                    'total_bruto_usd' => (float)($companySnap['bruto_total_usd'] ?? 0),
                    'total_liquido_usd' => (float)($companySnap['liquido_total_usd'] ?? 0),
                    'custos_usd' => (float)($companySnap['custos_usd'] ?? 0),
                    'custos_percentuais_usd' => (float)($companySnap['custos_percentuais'] ?? 0),
                    'lucro_liquido_usd' => (float)($companySnap['lucro_liquido_usd'] ?? 0),
                ];
                $snapSellers = $snapModel->loadSellersForPeriod($from, $to);
                $sellers = [];
                foreach ($snapSellers as $row) {
                    $sellers[] = [
                        'usuario_id' => (int)($row['seller_id'] ?? 0),
                        'name' => (string)($row['seller_name'] ?? ''),
                        'email' => (string)($row['seller_email'] ?? ''),
                        'role' => (string)($row['seller_role'] ?? ''),
                        'atendimentos' => (int)($row['atendimentos'] ?? 0),
                        'total_bruto_usd' => (float)($row['bruto_total_usd'] ?? 0),
                        'total_liquido_usd' => (float)($row['liquido_total_usd'] ?? 0),
                    ];
                }
                // Para commTeam/commItems, seguir usando cálculo ao vivo (comissões)
                $commCalc = (new Commission())->computeRange($from.' 00:00:00', $to.' 23:59:59');
            } else {
                $week = $report->summary($from, $to, $sellerId);
                $month = $week; // quando filtrado, usar mesmo sumário
                $sellers = $report->bySeller($from, $to);
                // Dados de comissões/empresa para o período filtrado
                $commCalc = (new Commission())->computeRange($from.' 00:00:00', $to.' 23:59:59');
            }
        } else {
            // Fallback (não deve ocorrer): usar semana atual e mês corrente
            $week = $report->weekSummary();
            $monthFrom = date('Y-m-01');
            $monthTo = date('Y-m-t');
            $month = $report->summary($monthFrom, $monthTo, null);
            $sellers = $report->bySeller($monthFrom, $monthTo);
            $commCalc = (new Commission())->computeRange($monthFrom.' 00:00:00', $monthTo.' 23:59:59');
        }
        $months = $report->lastMonthsComparison(3);

        // Excluir admins do desempenho (commItems)
        $commItems = array_values(array_filter(($commCalc['items'] ?? []), function($it){
            $role = $it['user']['role'] ?? '';
            return in_array($role, ['seller','trainee','manager'], true);
        }));

        $this->render('reports/index', [
            'title' => 'Relatórios',
            'rate' => $rate,
            'cost_rate' => $costRate,
            'week' => $week,
            'month' => $month,
            'months' => $months,
            'from' => $from,
            'to' => $to,
            'seller_id' => $sellerId,
            'users' => (new User())->allBasic(),
            // Commission/company overview
            'commTeam' => $commCalc['team'] ?? null,
            'commItems' => $commItems,
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

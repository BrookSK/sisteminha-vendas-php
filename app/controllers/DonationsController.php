<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Donation;
use Models\Commission;
use Models\Report;
use Models\Setting;

class DonationsController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30; $offset = ($page - 1) * $limit;

        $don = new Donation();
        $items = $don->list($limit, $offset, $from ?: null, $to ?: null, $q ?: null);
        $tot = $don->totals();

        // Lucro final da empresa no período e orçamento disponível para doações
        $report = new Report();
        // Se não houver período definido, considerar mês atual por padrão
        if (!$from || !$to) {
            $from = $from ?: date('Y-m-01');
            $to = $to ?: date('Y-m-t');
        }
        $summary = $report->summary($from, $to, null);
        $rate = 0.0;
        try { $set = new Setting(); $rate = (float)$set->get('usd_rate', '5.83'); } catch (\Throwable $e) { $rate = 5.83; }
        if ($rate <= 0) $rate = 5.83;
        // Novo critério: usar caixa da empresa no período (liquidos apurados - comissões)
        try {
            $comm = new Commission();
            $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
            $lucro_final_brl = (float)($calc['team']['company_cash_brl'] ?? (((float)($summary['lucro_liquido_usd'] ?? 0)) * $rate));
        } catch (\Throwable $e) {
            $lucro_final_brl = ((float)($summary['lucro_liquido_usd'] ?? 0)) * $rate;
        }
        $doadoPeriodo = $don->totalsPeriod($from, $to);
        $doado_brl = (float)($doadoPeriodo['total_doado_periodo_brl'] ?? 0);
        $orcamento_disponivel_brl = max(0, $lucro_final_brl - $doado_brl);

        $this->render('donations/index', [
            'title' => 'Doações',
            'items' => $items,
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'tot' => $tot,
            'lucro_final_brl' => $lucro_final_brl,
            'doado_brl' => $doado_brl,
            'orcamento_disponivel_brl' => $orcamento_disponivel_brl,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $data = [
            'instituicao' => trim($_POST['instituicao'] ?? ''),
            'cnpj' => trim($_POST['cnpj'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'valor_brl' => (float)($_POST['valor_brl'] ?? 0),
            'data' => $_POST['data'] ?? date('Y-m-d'),
            'categoria' => trim($_POST['categoria'] ?? ''),
        ];
        if ($data['instituicao'] === '' || $data['valor_brl'] <= 0) {
            return $this->redirect('/admin/donations');
        }
        (new Donation())->create($data, Auth::user()['id'] ?? null);
        return $this->redirect('/admin/donations');
    }

    public function update()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/donations');
        $data = [
            'instituicao' => trim($_POST['instituicao'] ?? ''),
            'cnpj' => trim($_POST['cnpj'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'valor_brl' => (float)($_POST['valor_brl'] ?? 0),
            'data' => $_POST['data'] ?? date('Y-m-d'),
            'categoria' => trim($_POST['categoria'] ?? ''),
        ];
        (new Donation())->updateRow($id, $data);
        return $this->redirect('/admin/donations');
    }

    public function cancel()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Donation())->cancel($id);
        }
        return $this->redirect('/admin/donations');
    }

    public function exportCsv()
    {
        $this->requireRole(['admin']);
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $q = trim($_GET['q'] ?? '');
        $items = (new Donation())->list(10000, 0, $from ?: null, $to ?: null, $q ?: null);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="doacoes.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Data','Instituicao','CNPJ','Categoria','Valor BRL','Status']);
        foreach ($items as $d) {
            fputcsv($out, [
                $d['id'], $d['data'], $d['instituicao'], $d['cnpj'], $d['categoria'],
                number_format((float)($d['valor_brl'] ?? 0), 2, '.', ''),
                $d['status']
            ]);
        }
        fclose($out);
        exit;
    }
}

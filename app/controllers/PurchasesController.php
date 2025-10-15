<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Purchase;

class PurchasesController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin','manager']); // manager como comprador designado (ajuste se houver flag específica)
        $status = $_GET['status'] ?? null; // pendente|comprado
        $resp = isset($_GET['responsavel_id']) && $_GET['responsavel_id'] !== '' ? (int)$_GET['responsavel_id'] : null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30; $offset = ($page - 1) * $limit;

        $items = (new Purchase())->list($limit, $offset, $resp, $status, $from ?: null, $to ?: null);
        $this->render('purchases/index', [
            'title' => 'Compras',
            'items' => $items,
            'status' => $status,
            'responsavel_id' => $resp,
            'from' => $from,
            'to' => $to,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function new()
    {
        $this->requireRole(['admin','manager']);
        $this->render('purchases/form', [
            'title' => 'Nova Compra',
            'action' => '/admin/purchases/create',
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->requireRole(['admin','manager']);
        $this->csrfCheck();
        $data = [
            'venda_id' => isset($_POST['venda_id']) && $_POST['venda_id']!=='' ? (int)$_POST['venda_id'] : null,
            'suite' => trim($_POST['suite'] ?? ''),
            'cliente_nome' => trim($_POST['cliente_nome'] ?? ''),
            'cliente_contato' => trim($_POST['cliente_contato'] ?? ''),
            'produto_link' => trim($_POST['produto_link'] ?? ''),
            'valor_usd' => (float)($_POST['valor_usd'] ?? 0),
            'nc_tax' => isset($_POST['nc_tax']) ? 1 : 0,
            'frete_aplicado' => isset($_POST['frete_aplicado']) ? 1 : 0,
            'frete_valor' => $_POST['frete_valor'] ?? null,
            'comprado' => isset($_POST['comprado']) ? 1 : 0,
            'data_compra' => $_POST['data_compra'] ?? null,
            'responsavel_id' => isset($_POST['responsavel_id']) && $_POST['responsavel_id']!=='' ? (int)$_POST['responsavel_id'] : (Auth::user()['id'] ?? null),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
        (new Purchase())->createManual($data);
        return $this->redirect('/admin/purchases');
    }

    public function update()
    {
        $this->requireRole(['admin','manager']);
        $this->csrfCheck();
        $data = [
            'id' => (int)($_POST['id'] ?? 0),
            'nc_tax' => isset($_POST['nc_tax']) ? 1 : 0,
            'frete_aplicado' => isset($_POST['frete_aplicado']) ? 1 : 0,
            'frete_valor' => $_POST['frete_valor'] ?? '',
            'comprado' => isset($_POST['comprado']) ? 1 : 0,
            'data_compra' => $_POST['data_compra'] ?? null,
            'responsavel_id' => $_POST['responsavel_id'] ?? null,
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
        if ($data['id'] > 0) {
            (new Purchase())->updateRow($data);
        }
        return $this->redirect('/admin/purchases');
    }

    public function exportCsv()
    {
        $this->requireRole(['admin','manager']);
        $status = $_GET['status'] ?? null;
        $resp = isset($_GET['responsavel_id']) && $_GET['responsavel_id'] !== '' ? (int)$_GET['responsavel_id'] : null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $items = (new Purchase())->list(10000, 0, $resp, $status, $from ?: null, $to ?: null);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="compras.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Venda','Cliente','Suite','Link','Valor USD','NC 7%','Frete Aplicado','Frete Valor','Comprado','Data Compra','Responsavel ID']);
        foreach ($items as $p) {
            fputcsv($out, [
                $p['id'], $p['venda_id'], $p['cliente_nome'], $p['suite'], $p['produto_link'],
                number_format((float)($p['valor_usd'] ?? 0), 2, '.', ''),
                (int)($p['nc_tax'] ?? 0), (int)($p['frete_aplicado'] ?? 0),
                $p['frete_valor'] !== null ? number_format((float)$p['frete_valor'], 2, '.', '') : '',
                (int)($p['comprado'] ?? 0), $p['data_compra'] ?? '', $p['responsavel_id'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }
}

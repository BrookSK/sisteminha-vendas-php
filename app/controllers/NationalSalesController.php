<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\NationalSale;
use Models\Client;
use Models\Setting;
use Models\Purchase;
use Models\User;

class NationalSalesController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;
        $ym = $_GET['ym'] ?? null;
        $me = Auth::user();
        if (in_array(($me['role'] ?? 'seller'), ['seller'], true)) {
            $sellerId = (int)($me['id'] ?? 0) ?: null;
        }
        $items = (new NationalSale())->list(200, 0, $sellerId, $ym);
        $users = (new User())->allBasic();
        $this->render('national_sales/index', [
            'title' => 'Vendas Nacionais',
            'items' => $items,
            'seller_id' => $sellerId,
            'ym' => $ym,
            'users' => $users,
        ]);
    }

    public function new()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $clients = (new Client())->search(null, 1000, 0);
        $rate = (float)((new Setting())->get('usd_rate', '5.83'));
        $this->render('national_sales/form', [
            'title' => 'Nova Venda Nacional',
            'action' => '/admin/national-sales/create',
            'sale' => null,
            'clients' => $clients,
            'now' => date('Y-m-d'),
            'rate' => $rate,
        ]);
    }

    public function create()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $this->csrfCheck();
        $data = $this->collect($_POST);
        $me = Auth::user();
        // Require client selection server-side to avoid FK error
        if (empty($data['cliente_id']) || (int)$data['cliente_id'] <= 0) {
            return $this->redirect('/admin/national-sales/new');
        }
        // Ignore manual observation on create; system manages it automatically on date edits
        $data['observacao'] = null;
        // Sellers cannot override USD rate; enforce Settings value
        if (($me['role'] ?? 'seller') === 'seller') {
            $data['taxa_dolar'] = (float)((new Setting())->get('usd_rate', '5.83'));
        }
        $id = (new NationalSale())->create($data, (int)($me['id'] ?? 0));
        (new Purchase())->upsertFromNat($id);
        return $this->redirect('/admin/national-sales');
    }

    public function edit()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $id = (int)($_GET['id'] ?? 0);
        $model = new NationalSale();
        $row = $model->find($id);
        if (!$row) return $this->redirect('/admin/national-sales');
        $me = Auth::user();
        if (($me['role'] ?? 'seller') === 'seller' && (int)$row['vendedor_id'] !== (int)($me['id'] ?? 0)) {
            return $this->redirect('/admin/national-sales');
        }
        $clients = (new Client())->search(null, 1000, 0);
        $this->render('national_sales/form', [
            'title' => 'Editar Venda Nacional',
            'action' => '/admin/national-sales/update?id='.$id,
            'sale' => $row,
            'clients' => $clients,
            'now' => date('Y-m-d'),
        ]);
    }

    public function update()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $this->csrfCheck();
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->collect($_POST);
        $me = Auth::user();
        // Require client selection server-side as safeguard
        if (empty($data['cliente_id']) || (int)$data['cliente_id'] <= 0) {
            return $this->redirect('/admin/national-sales/edit?id=' . $id);
        }
        // permitir alteração de data para seller/manager/admin
        $allowDateChange = true;
        // Enforce USD rate for sellers on update as well
        if (($me['role'] ?? 'seller') === 'seller') {
            $data['taxa_dolar'] = (float)((new Setting())->get('usd_rate', '5.83'));
        }
        (new NationalSale())->update($id, $data, (string)($me['name'] ?? $me['email'] ?? ''), $allowDateChange);
        (new Purchase())->upsertFromNat($id);
        return $this->redirect('/admin/national-sales');
    }

    public function duplicate()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $id = (int)($_GET['id'] ?? 0);
        $model = new \Models\NationalSale();
        $row = $model->find($id);
        if (!$row) return $this->redirect('/admin/national-sales');
        $me = Auth::user();
        if (($me['role'] ?? 'seller') === 'seller' && (int)$row['vendedor_id'] !== (int)($me['id'] ?? 0)) {
            return $this->redirect('/admin/national-sales');
        }
        $data = [
            'data_lancamento' => date('Y-m-d'),
            'numero_pedido' => '',
            'cliente_id' => (int)$row['cliente_id'],
            'suite_cliente' => (string)($row['suite_cliente'] ?? ''),
            'peso_kg' => (float)($row['peso_kg'] ?? 0),
            'valor_produto_usd' => (float)($row['valor_produto_usd'] ?? 0),
            'taxa_servico_usd' => (float)($row['taxa_servico_usd'] ?? 0),
            'servico_compra_usd' => (float)($row['servico_compra_usd'] ?? 0),
            'produtos_compra_usd' => (float)($row['produtos_compra_usd'] ?? 0),
            'taxa_dolar' => (float)($row['taxa_dolar'] ?? 0),
            'observacao' => null,
        ];
        $newId = $model->create($data, (int)($row['vendedor_id'] ?? ($me['id'] ?? 0)));
        return $this->redirect('/admin/national-sales/edit?id='.$newId.'&dup=1');
    }

    public function exportCsv()
    {
        $this->requireRole(['manager','admin']);
        $filters = [
            'seller_id' => isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null,
            'ym' => $_GET['ym'] ?? null,
        ];
        $csv = (new NationalSale())->exportCsv($filters);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vendas_nacionais.csv"');
        echo $csv;
        exit;
    }

    public function data()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;
        $ym = $_GET['ym'] ?? null;
        $me = Auth::user();
        if (($me['role'] ?? 'seller') === 'seller') {
            $sellerId = (int)($me['id'] ?? 0) ?: null;
        }
        $items = (new NationalSale())->list(5000, 0, $sellerId, $ym);
        header('Content-Type: application/json');
        echo json_encode(['data' => $items]);
        exit;
    }

    private function collect(array $in): array
    {
        return [
            'data_lancamento' => $in['data_lancamento'] ?? date('Y-m-d'),
            'numero_pedido' => trim($in['numero_pedido'] ?? ''),
            'cliente_id' => (int)($in['cliente_id'] ?? 0),
            'suite_cliente' => trim($in['suite_cliente'] ?? ''),
            'peso_kg' => (float)($in['peso_kg'] ?? 0),
            'valor_produto_usd' => (float)($in['valor_produto_usd'] ?? 0),
            'taxa_servico_usd' => (float)($in['taxa_servico_usd'] ?? 0),
            'servico_compra_usd' => (float)($in['servico_compra_usd'] ?? 0),
            // frete calculado no model
            'produtos_compra_usd' => (float)($in['produtos_compra_usd'] ?? 0),
            'taxa_dolar' => (float)($in['taxa_dolar'] ?? 0),
            'observacao' => trim($in['observacao'] ?? ''),
        ];
    }
}

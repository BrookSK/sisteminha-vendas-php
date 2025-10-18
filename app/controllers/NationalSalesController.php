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
        $fromId = isset($_GET['from']) ? (int)$_GET['from'] : 0;
        $prefill = null;
        if ($fromId > 0) {
            $src = (new NationalSale())->find($fromId);
            if ($src) {
                $prefill = [
                    'data_lancamento' => date('Y-m-d'),
                    'numero_pedido' => '',
                    'cliente_id' => (int)($src['cliente_id'] ?? 0),
                    'suite_cliente' => (string)($src['suite_cliente'] ?? ''),
                    'peso_kg' => (float)($src['peso_kg'] ?? 0),
                    'valor_produto_usd' => (float)($src['valor_produto_usd'] ?? 0),
                    'taxa_servico_usd' => (float)($src['taxa_servico_usd'] ?? 0),
                    'servico_compra_usd' => (float)($src['servico_compra_usd'] ?? 0),
                    'produtos_compra_usd' => (float)($src['produtos_compra_usd'] ?? 0),
                    'taxa_dolar' => (float)($src['taxa_dolar'] ?? $rate),
                    'frete_correios_brl' => (float)($src['frete_correios_brl'] ?? 0),
                    'frete_correios_usd' => (float)($src['frete_correios_usd'] ?? 0),
                    'total_bruto_usd' => (float)($src['total_bruto_usd'] ?? 0),
                    'total_bruto_brl' => (float)($src['total_bruto_brl'] ?? 0),
                    'total_liquido_usd' => (float)($src['total_liquido_usd'] ?? 0),
                    'total_liquido_brl' => (float)($src['total_liquido_brl'] ?? 0),
                    'observacao' => '',
                ];
            }
        }
        $this->render('national_sales/form', [
            'title' => 'Nova Venda Nacional',
            'action' => '/admin/national-sales/create',
            'sale' => $prefill,
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
        // Não persiste, apenas redireciona para o formulário novo com prefill
        return $this->redirect('/admin/national-sales/new?dup=1&from='.$id);
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

    public function delete()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/national-sales');
        $model = new NationalSale();
        $row = $model->find($id);
        if (!$row) return $this->redirect('/admin/national-sales');
        $me = Auth::user();
        if (($me['role'] ?? 'seller') === 'seller' && (int)$row['vendedor_id'] !== (int)($me['id'] ?? 0)) {
            return $this->redirect('/admin/national-sales');
        }
        $model->delete($id);
        return $this->redirect('/admin/national-sales');
    }

    public function data()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;
        $ym = $_GET['ym'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, min(100, (int)($_GET['per'] ?? 25)));
        $me = Auth::user();
        if (($me['role'] ?? 'seller') === 'seller') {
            $sellerId = (int)($me['id'] ?? 0) ?: null;
        }
        $model = new NationalSale();
        $total = $model->count($sellerId, $ym);
        $offset = ($page - 1) * $per;
        $items = $model->list($per, $offset, $sellerId, $ym);
        header('Content-Type: application/json');
        echo json_encode(['data' => $items, 'page'=>$page, 'per'=>$per, 'total'=>$total]);
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

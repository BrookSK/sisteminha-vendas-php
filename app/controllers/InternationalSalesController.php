<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\InternationalSale;
use Models\Client;
use Models\Setting;
use Models\Purchase;
use Models\User;
use Models\Approval;
use Models\Notification;

class InternationalSalesController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;
        $ym = $_GET['ym'] ?? null; // YYYY-MM
        $q = isset($_GET['q']) ? (string)$_GET['q'] : null;
        // Sellers can only see their own unless manager/admin
        $me = Auth::user();
        if (in_array(($me['role'] ?? 'seller'), ['seller','trainee'], true)) {
            $sellerId = (int)($me['id'] ?? 0) ?: null;
        }
        $items = (new InternationalSale())->list(200, 0, $sellerId, $ym, $q);
        $users = (new User())->allBasic();
        $this->render('international_sales/index', [
            'title' => 'Vendas Internacionais',
            'items' => $items,
            'seller_id' => $sellerId,
            'ym' => $ym,
            'q' => $q,
            'users' => $users,
        ]);
    }

    public function new()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $clients = (new Client())->search(null, 1000, 0);
        $rate = (float)((new Setting())->get('usd_rate', '5.83'));
        $fromId = isset($_GET['from']) ? (int)$_GET['from'] : 0;
        $prefill = null;
        if ($fromId > 0) {
            $src = (new InternationalSale())->find($fromId);
            if ($src) {
                $prefill = [
                    'data_lancamento' => date('Y-m-d'),
                    'numero_pedido' => '',
                    'cliente_id' => (int)($src['cliente_id'] ?? 0),
                    'suite_cliente' => (string)($src['suite_cliente'] ?? ''),
                    'peso_kg' => (float)($src['peso_kg'] ?? 0),
                    'valor_produto_usd' => (float)($src['valor_produto_usd'] ?? 0),
                    'frete_ups_usd' => (float)($src['frete_ups_usd'] ?? 0),
                    'valor_redirecionamento_usd' => (float)($src['valor_redirecionamento_usd'] ?? 0),
                    'servico_compra_usd' => (float)($src['servico_compra_usd'] ?? 0),
                    'frete_etiqueta_usd' => (float)($src['frete_etiqueta_usd'] ?? 0),
                    'produtos_compra_usd' => (float)($src['produtos_compra_usd'] ?? 0),
                    'taxa_dolar' => (float)($src['taxa_dolar'] ?? $rate),
                    'total_bruto_usd' => (float)($src['total_bruto_usd'] ?? 0),
                    'total_bruto_brl' => (float)($src['total_bruto_brl'] ?? 0),
                    'total_liquido_usd' => (float)($src['total_liquido_usd'] ?? 0),
                    'total_liquido_brl' => (float)($src['total_liquido_brl'] ?? 0),
                    'comissao_usd' => (float)($src['comissao_usd'] ?? 0),
                    'comissao_brl' => (float)($src['comissao_brl'] ?? 0),
                    'observacao' => '',
                ];
            }
        }
        $this->render('international_sales/form', [
            'title' => 'Nova Venda Internacional',
            'action' => '/admin/international-sales/create',
            'sale' => $prefill,
            'clients' => $clients,
            'now' => date('Y-m-d'),
            'rate' => $rate,
        ]);
    }

    public function create()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $data = $this->collect($_POST);
        $me = Auth::user();
        // Require client selection server-side to avoid FK error
        if (empty($data['cliente_id']) || (int)$data['cliente_id'] <= 0) {
            $this->flash('danger', 'Selecione um cliente. Se cadastrou agora em outra aba, clique em "Atualizar lista" e selecione o cliente antes de salvar.');
            return $this->redirect('/admin/international-sales/new');
        }
        // Ignore manual observation on create; system manages it automatically on date edits
        $data['observacao'] = null;
        // Sellers/trainees cannot override USD rate; enforce Settings value
        if (in_array(($me['role'] ?? 'seller'), ['seller','trainee'], true)) {
            $data['taxa_dolar'] = (float)((new Setting())->get('usd_rate', '5.83'));
        }
        if (($me['role'] ?? 'seller') === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $apprId = (new Approval())->createPending('intl_sale', 'create', $data, (int)($me['id'] ?? 0), $supervisorId, null);
            if ($supervisorId) {
                $traineeName = (string)($meFull['name'] ?? $me['name'] ?? $me['email'] ?? 'Trainee');
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Aprovação de Venda (Internacional)', 'Uma nova venda internacional foi enviada por ' . $traineeName . ' e aguarda sua aprovação. [approval-id:' . $apprId . ']', 'approval', 'new', [$supervisorId]);
            }
            $this->flash('info', 'Venda enviada para aprovação. Uma notificação foi enviada ao seu supervisor e está pendente de aprovação.');
            return $this->redirect('/admin/international-sales');
        }
        $id = (new InternationalSale())->create($data, (int)($me['id'] ?? 0), (string)($me['name'] ?? $me['email'] ?? ''));
        (new Purchase())->upsertFromIntl($id);
        return $this->redirect('/admin/international-sales');
    }

    public function duplicate()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $id = (int)($_GET['id'] ?? 0);
        $model = new \Models\InternationalSale();
        $row = $model->find($id);
        if (!$row) return $this->redirect('/admin/international-sales');
        $me = Auth::user();
        if (in_array(($me['role'] ?? 'seller'), ['seller','trainee'], true) && (int)$row['vendedor_id'] !== (int)($me['id'] ?? 0)) {
            return $this->redirect('/admin/international-sales');
        }
        // Não persiste, apenas redireciona para o formulário novo com prefill
        return $this->redirect('/admin/international-sales/new?dup=1&from='.$id);
    }

    public function edit()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $id = (int)($_GET['id'] ?? 0);
        $model = new InternationalSale();
        $row = $model->find($id);
        if (!$row) return $this->redirect('/admin/international-sales');
        // Sellers/Trainees can only edit own
        $me = Auth::user();
        if (in_array(($me['role'] ?? 'seller'), ['seller','trainee'], true) && (int)$row['vendedor_id'] !== (int)($me['id'] ?? 0)) {
            return $this->redirect('/admin/international-sales');
        }
        $clients = (new Client())->search(null, 1000, 0);
        $this->render('international_sales/form', [
            'title' => 'Editar Venda Internacional',
            'action' => '/admin/international-sales/update?id='.$id,
            'sale' => $row,
            'clients' => $clients,
            'now' => date('Y-m-d'),
        ]);
    }

    public function update()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->collect($_POST);
        $me = Auth::user();
        // Require client selection server-side as safeguard
        if (empty($data['cliente_id']) || (int)$data['cliente_id'] <= 0) {
            $this->flash('danger', 'Selecione um cliente. Se cadastrou agora em outra aba, clique em "Atualizar lista" e selecione o cliente antes de salvar.');
            return $this->redirect('/admin/international-sales/edit?id=' . $id);
        }
        // permitir alteração de data para seller/manager/admin
        $allowDateChange = true;
        // Enforce USD rate for sellers/trainees on update as well
        if (in_array(($me['role'] ?? 'seller'), ['seller','trainee'], true)) {
            $data['taxa_dolar'] = (float)((new Setting())->get('usd_rate', '5.83'));
        }
        if (($me['role'] ?? 'seller') === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $apprId = (new Approval())->createPending('intl_sale', 'update', ['id'=>$id,'data'=>$data], (int)($me['id'] ?? 0), $supervisorId, $id);
            if ($supervisorId) {
                $traineeName = (string)($meFull['name'] ?? $me['name'] ?? $me['email'] ?? 'Trainee');
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Aprovação de Venda (Internacional) - Edição', 'Uma edição de venda internacional foi enviada por ' . $traineeName . ' e aguarda sua aprovação. [approval-id:' . $apprId . ']', 'approval', 'new', [$supervisorId]);
            }
            $this->flash('info', 'Edição enviada para aprovação. Uma notificação foi enviada ao seu supervisor e está pendente de aprovação.');
            return $this->redirect('/admin/international-sales');
        }
        (new InternationalSale())->update($id, $data, (string)($me['name'] ?? $me['email'] ?? ''), $allowDateChange);
        (new Purchase())->upsertFromIntl($id);
        return $this->redirect('/admin/international-sales');
    }

    public function exportCsv()
    {
        $this->requireRole(['manager','admin']);
        $filters = [
            'seller_id' => isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null,
            'ym' => $_GET['ym'] ?? null,
        ];
        $csv = (new InternationalSale())->exportCsv($filters);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vendas_internacionais.csv"');
        echo $csv;
        exit;
    }

    public function delete()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/international-sales');
        $model = new InternationalSale();
        $row = $model->find($id);
        if (!$row) return $this->redirect('/admin/international-sales');
        $me = Auth::user();
        if (in_array(($me['role'] ?? 'seller'), ['seller','trainee'], true) && (int)$row['vendedor_id'] !== (int)($me['id'] ?? 0)) {
            return $this->redirect('/admin/international-sales');
        }
        if (($me['role'] ?? 'seller') === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $traineeName = (string)($meFull['name'] ?? $me['name'] ?? $me['email'] ?? 'Trainee');
            // Notificar todos admins para excluir diretamente
            $admins = (new User())->listByRoles(['admin']);
            $adminIds = array_map(function($u){ return (int)$u['id']; }, $admins);
            if (!empty($adminIds)) {
                $title = 'Ação Necessária: Excluir Venda (Internacional)';
                $msg = 'Solicitação de exclusão enviada por ' . $traineeName . '. Venda internacional #' . $id . '. Acesse o cadastro da venda e exclua diretamente: /admin/international-sales/edit?id=' . $id;
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), $title, $msg, 'approval', 'new', $adminIds);
            }
            $this->flash('info','Sua solicitação foi enviada. Um administrador irá excluir a venda.');
            return $this->redirect('/admin/international-sales');
        }
        $model->delete($id);
        return $this->redirect('/admin/international-sales');
    }

    public function data()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;
        $ym = $_GET['ym'] ?? null;
        $q = isset($_GET['q']) ? (string)$_GET['q'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, min(100, (int)($_GET['per'] ?? 20)));
        $me = Auth::user();
        if (($me['role'] ?? 'seller') === 'seller') {
            $sellerId = (int)($me['id'] ?? 0) ?: null;
        }
        $model = new InternationalSale();
        $total = $model->count($sellerId, $ym, $q);
        $offset = ($page - 1) * $per;
        $items = $model->list($per, $offset, $sellerId, $ym, $q);
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
            'frete_ups_usd' => (float)($in['frete_ups_usd'] ?? 0),
            'valor_redirecionamento_usd' => (float)($in['valor_redirecionamento_usd'] ?? 0),
            'servico_compra_usd' => (float)($in['servico_compra_usd'] ?? 0),
            'frete_etiqueta_usd' => (float)($in['frete_etiqueta_usd'] ?? 0),
            'produtos_compra_usd' => (float)($in['produtos_compra_usd'] ?? 0),
            'taxa_dolar' => (float)($in['taxa_dolar'] ?? 0),
            'observacao' => trim($in['observacao'] ?? ''),
        ];
    }
}

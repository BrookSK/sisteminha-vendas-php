<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Sale;
use Models\Client;
use Models\Setting;
use Models\Log;
use Models\Commission;
use Models\Purchase;

class SalesController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $sale = new Sale();
        $user = Auth::user();
        $role = $user['role'] ?? 'seller';
        $userId = ($role === 'seller') ? ($user['id'] ?? null) : null;
        $sales = $sale->list(100, 0, $userId);
        $this->render('sales/index', [
            'title' => 'Vendas',
            'sales' => $sales,
        ]);
    }

    public function new()
    {
        $this->requireRole(['seller','manager','admin']);
        $clients = (new Client())->search(null, 1000, 0);
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $emb = (float)$setting->get('embalagem_usd_por_kg', '9.70');
        $this->render('sales/form', [
            'title' => 'Nova Venda',
            'action' => '/admin/sales/create',
            'clients' => $clients,
            'rate' => $rate,
            'emb' => $emb,
            'sale' => null,
        ]);
    }

    public function create()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $data = $this->collect($_POST);
        // Period lock: only allow creating sales within current period
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $createdAt = $data['created_at'] ?: date('Y-m-d H:i:s');
        if ($set && !$set->isInCurrentPeriodDateTime($createdAt)) {
            $this->flash('danger', 'Criação bloqueada: somente é permitido lançar vendas dentro do período atual (10 ao 9).');
            return $this->redirect('/admin/sales');
        }
        // Validate selected client (handles stale form opened before creating client in another tab)
        if ((int)($data['cliente_id'] ?? 0) <= 0) {
            $this->flash('danger', 'Selecione um cliente. Se cadastrou agora em outra aba, use o botão "Atualizar lista" no campo Cliente e selecione novamente.');
            return $this->redirect('/admin/sales/new');
        }
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $emb = (float)$setting->get('embalagem_usd_por_kg', '9.70');
        $sale = new Sale();
        $id = $sale->create($data, $rate, Auth::user()['id'] ?? null, $emb);
        (new Log())->add(Auth::user()['id'] ?? null, 'venda', 'create', $id, json_encode($data));
        // Atualiza fila de compras, se houver link do produto
        if (!empty($data['produto_link'])) { (new Purchase())->upsertFromSale($id); }
        // Recalcula comissões do mês atual
        (new Commission())->recalcMonthly(date('Y-m'));
        $this->redirect('/admin/sales');
    }

    public function edit()
    {
        $this->requireRole(['manager','admin']);
        $id = (int)($_GET['id'] ?? 0);
        $sale = new Sale();
        $row = $sale->find($id);
        if (!$row) return $this->redirect('/admin/sales');
        $clients = (new Client())->search(null, 1000, 0);
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $emb = (float)$setting->get('embalagem_usd_por_kg', '9.70');
        $this->render('sales/form', [
            'title' => 'Editar Venda',
            'action' => '/admin/sales/update?id=' . $id,
            'clients' => $clients,
            'rate' => $rate,
            'emb' => $emb,
            'sale' => $row,
        ]);
    }

    public function update()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->collect($_POST);
        // Period lock: block updates for sales outside current period
        $sale = new Sale();
        $row = $sale->find($id);
        if ($row) {
            try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
            if ($set && ! $set->isInCurrentPeriodDateTime((string)($row['created_at'] ?? ''))) {
                $this->flash('danger', 'Edição bloqueada: vendas de períodos anteriores (10 ao 9) não podem ser alteradas.');
                return $this->redirect('/admin/sales');
            }
        }
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $emb = (float)$setting->get('embalagem_usd_por_kg', '9.70');
        $sale->update($id, $data, $rate, $emb);
        (new Log())->add(Auth::user()['id'] ?? null, 'venda', 'update', $id, json_encode($data));
        if (!empty($data['produto_link'])) { (new Purchase())->upsertFromSale($id); }
        (new Commission())->recalcMonthly(date('Y-m'));
        $this->redirect('/admin/sales');
    }

    public function delete()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Period lock: block deletion for sales outside current period
            $s = new Sale();
            $row = $s->find($id);
            if ($row) {
                try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
                if ($set && ! $set->isInCurrentPeriodDateTime((string)($row['created_at'] ?? ''))) {
                    $this->flash('danger', 'Exclusão bloqueada: vendas de períodos anteriores (10 ao 9) não podem ser excluídas.');
                    return $this->redirect('/admin/sales');
                }
            }
            $s->delete($id);
            (new Log())->add(Auth::user()['id'] ?? null, 'venda', 'delete', $id, null);
            (new Commission())->recalcMonthly(date('Y-m'));
        }
        $this->redirect('/admin/sales');
    }

    public function search()
    {
        $this->requireRole(['seller','manager','admin','organic']);
        $q = trim($_GET['q'] ?? '');
        $pdo = \Core\Database::pdo();
        $like = '%'.$q.'%';
        $sqlIntl = "SELECT v.id, COALESCE(v.numero_pedido, v.id_externo, v.id) AS numero, 'intl' AS tipo, c.nome AS cliente FROM vendas_internacionais v LEFT JOIN clientes c ON c.id=v.cliente_id WHERE (:q='') OR (v.numero_pedido LIKE :like1 OR v.id_externo LIKE :like2 OR CAST(v.id AS CHAR) LIKE :like3) ORDER BY v.id DESC LIMIT 50";
        $st1 = $pdo->prepare($sqlIntl);
        $st1->bindValue(':q', $q, \PDO::PARAM_STR);
        $st1->bindValue(':like1', $like, \PDO::PARAM_STR);
        $st1->bindValue(':like2', $like, \PDO::PARAM_STR);
        $st1->bindValue(':like3', $like, \PDO::PARAM_STR);
        $st1->execute();
        $res1 = $st1->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $sqlNat = "SELECT v.id, COALESCE(v.numero_pedido, v.id_externo, v.id) AS numero, 'nat' AS tipo, c.nome AS cliente FROM vendas_nacionais v LEFT JOIN clientes c ON c.id=v.cliente_id WHERE (:q='') OR (v.numero_pedido LIKE :like1 OR v.id_externo LIKE :like2 OR CAST(v.id AS CHAR) LIKE :like3) ORDER BY v.id DESC LIMIT 50";
        $st2 = $pdo->prepare($sqlNat);
        $st2->bindValue(':q', $q, \PDO::PARAM_STR);
        $st2->bindValue(':like1', $like, \PDO::PARAM_STR);
        $st2->bindValue(':like2', $like, \PDO::PARAM_STR);
        $st2->bindValue(':like3', $like, \PDO::PARAM_STR);
        $st2->execute();
        $res2 = $st2->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach (array_merge($res1, $res2) as $r) {
            $text = '#'.$r['id'].' - '.(string)($r['numero'] ?? '').' ['.$r['tipo'].']';
            if (!empty($r['cliente'])) $text .= ' - '.$r['cliente'];
            $out[] = ['id'=>(int)$r['id'], 'tipo'=>$r['tipo'], 'text'=>$text];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }

    private function collect(array $in): array
    {
        return [
            'created_at' => trim($in['created_at'] ?? ''),
            'numero_pedido' => trim($in['numero_pedido'] ?? ''),
            'cliente_id' => (int)($in['cliente_id'] ?? 0),
            'suite' => trim($in['suite'] ?? ''),
            'peso_kg' => (float)($in['peso_kg'] ?? 0),
            'valor_produto_usd' => (float)($in['valor_produto_usd'] ?? 0),
            'taxa_servico_usd' => (float)($in['taxa_servico_usd'] ?? 0),
            'servico_compra_usd' => (float)($in['servico_compra_usd'] ?? 0),
            'produto_compra_usd' => (float)($in['produto_compra_usd'] ?? 0),
            'produto_link' => trim($in['produto_link'] ?? ''),
            'origem' => in_array(($in['origem'] ?? null), ['organica','lead','pay-per-click'], true) ? $in['origem'] : null,
            'nc_tax' => isset($in['nc_tax']) ? 1 : 0,
            'frete_manual_valor' => (isset($in['frete_aplicado']) && $in['frete_aplicado']) ? (string)($in['frete_manual_valor'] ?? '') : '',
        ];
    }

}

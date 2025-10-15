<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Container;
use Models\Setting;

class ContainersController extends Controller
{
    private function ensureRole(): void
    {
        $this->requireRole(['admin','manager']);
    }

    public function index()
    {
        $this->ensureRole();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30; $offset = ($page - 1) * $limit;
        $items = (new Container())->list($limit, $offset);
        $this->render('containers/index', [
            'title' => 'Containers',
            'items' => $items,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function new()
    {
        $this->ensureRole();
        $set = new Setting();
        $this->render('containers/form', [
            'title' => 'Novo Container',
            'action' => '/admin/containers/create',
            'statuses' => Container::statuses(),
            'rate' => (float)$set->get('usd_rate', '5.83'),
            'lbs_per_kg' => (float)$set->get('lbs_per_kg', '2.2'),
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->ensureRole();
        $this->csrfCheck();
        $data = [
            'utilizador_id' => isset($_POST['utilizador_id']) && $_POST['utilizador_id']!=='' ? (int)$_POST['utilizador_id'] : (Auth::user()['id'] ?? null),
            'invoice_id' => trim($_POST['invoice_id'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Em preparo'),
            'created_at' => trim($_POST['created_at'] ?? date('Y-m-d')),
            'peso_kg' => (float)($_POST['peso_kg'] ?? 0),
            'transporte_aeroporto_correios_brl' => (float)($_POST['transporte_aeroporto_correios_brl'] ?? 0),
            'transporte_mercadoria_usd' => (float)($_POST['transporte_mercadoria_usd'] ?? 0),
            'vendas_ids' => trim($_POST['vendas_ids'] ?? ''),
        ];
        (new Container())->create($data);
        return $this->redirect('/admin/containers');
    }

    public function edit()
    {
        $this->ensureRole();
        $id = (int)($_GET['id'] ?? 0);
        $row = (new Container())->find($id);
        if (!$row) { return $this->redirect('/admin/containers'); }
        $set = new Setting();
        $this->render('containers/form', [
            'title' => 'Editar Container',
            'action' => '/admin/containers/update?id='.$id,
            'row' => $row,
            'statuses' => Container::statuses(),
            'rate' => (float)$set->get('usd_rate', '5.83'),
            'lbs_per_kg' => (float)$set->get('lbs_per_kg', '2.2'),
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function update()
    {
        $this->ensureRole();
        $this->csrfCheck();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/containers');
        $data = [
            'utilizador_id' => isset($_POST['utilizador_id']) && $_POST['utilizador_id']!=='' ? (int)$_POST['utilizador_id'] : (Auth::user()['id'] ?? null),
            'invoice_id' => trim($_POST['invoice_id'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Em preparo'),
            'created_at' => trim($_POST['created_at'] ?? date('Y-m-d')),
            'peso_kg' => (float)($_POST['peso_kg'] ?? 0),
            'transporte_aeroporto_correios_brl' => (float)($_POST['transporte_aeroporto_correios_brl'] ?? 0),
            'transporte_mercadoria_usd' => (float)($_POST['transporte_mercadoria_usd'] ?? 0),
            'vendas_ids' => trim($_POST['vendas_ids'] ?? ''),
        ];
        (new Container())->update($id, $data);
        return $this->redirect('/admin/containers');
    }

    public function delete()
    {
        $this->ensureRole();
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Container())->delete($id);
        }
        return $this->redirect('/admin/containers');
    }
}

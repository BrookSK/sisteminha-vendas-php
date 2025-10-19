<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\SiteClient;

class SiteClientsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $list = (new SiteClient())->list(500, 0);
        $this->render('site_clients/index', [
            'title' => 'Clientes (Sites)',
            'clients' => $list,
        ]);
    }

    public function create()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];
        if ($d['name'] === '') { $this->flash('warning','Informe o nome'); return $this->redirect('/admin/site-clients'); }
        (new SiteClient())->create($d);
        $this->flash('success','Cliente criado.');
        return $this->redirect('/admin/site-clients');
    }

    public function update()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/site-clients');
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];
        (new SiteClient())->updateRow($id, $d);
        $this->flash('success','Cliente atualizado.');
        return $this->redirect('/admin/site-clients');
    }

    public function delete()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { (new SiteClient())->delete($id); $this->flash('success','Cliente excluído.'); }
        return $this->redirect('/admin/site-clients');
    }

    // GET /admin/site-clients/options?q=...
    public function options()
    {
        $this->requireRole(['seller','manager','admin']);
        $q = trim($_GET['q'] ?? '');
        $rows = (new SiteClient())->searchLite($q ?: null, 20, 0);
        $out = [];
        foreach ($rows as $r) {
            $label = $r['name'];
            if (!empty($r['email'])) $label .= ' <'.$r['email'].'>';
            $out[] = ['id'=>(int)$r['id'], 'text'=>$label];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Hosting;
use Models\HostingAsset;

class HostingsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $hostings = (new Hosting())->list(1000, 0);
        $this->render('hostings/index', [
            'title' => 'Hospedagens',
            'hostings' => $hostings,
        ]);
    }

    public function create()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $d = [
            'provider' => trim($_POST['provider'] ?? ''),
            'server_name' => trim($_POST['server_name'] ?? ''),
            'plan_name' => trim($_POST['plan_name'] ?? ''),
            'price' => $_POST['price'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'due_day' => $_POST['due_day'] ?? null,
            'billing_cycle' => $_POST['billing_cycle'] ?? 'mensal',
            'server_ip' => trim($_POST['server_ip'] ?? ''),
            'auto_payment' => !empty($_POST['auto_payment']) ? 1 : 0,
            'login_email' => trim($_POST['login_email'] ?? ''),
            'payer_responsible' => trim($_POST['payer_responsible'] ?? ''),
            'status' => $_POST['status'] ?? 'ativo',
            'description' => $_POST['description'] ?? null,
        ];
        if ($d['provider'] === '' || $d['server_name'] === '') { $this->flash('warning', 'Preencha provedor e nome do servidor.'); return $this->redirect('/admin/hostings'); }
        (new Hosting())->create($d, $u['id'] ?? null);
        $this->flash('success', 'Hospedagem criada.');
        return $this->redirect('/admin/hostings');
    }

    public function update()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/hostings');
        $d = [
            'provider' => trim($_POST['provider'] ?? ''),
            'server_name' => trim($_POST['server_name'] ?? ''),
            'plan_name' => trim($_POST['plan_name'] ?? ''),
            'price' => $_POST['price'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'due_day' => $_POST['due_day'] ?? null,
            'billing_cycle' => $_POST['billing_cycle'] ?? 'mensal',
            'server_ip' => trim($_POST['server_ip'] ?? ''),
            'auto_payment' => !empty($_POST['auto_payment']) ? 1 : 0,
            'login_email' => trim($_POST['login_email'] ?? ''),
            'payer_responsible' => trim($_POST['payer_responsible'] ?? ''),
            'status' => $_POST['status'] ?? 'ativo',
            'description' => $_POST['description'] ?? null,
        ];
        (new Hosting())->updateRow($id, $d, $u['id'] ?? null);
        $this->flash('success', 'Hospedagem atualizada.');
        return $this->redirect('/admin/hostings');
    }

    public function delete()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $assets = (new HostingAsset())->list(1,0,$id);
            if (!empty($assets)) {
                $this->flash('danger', 'Não é possível excluir: existem ativos vinculados a esta hospedagem.');
            } else {
                (new Hosting())->delete($id);
                $this->flash('success', 'Hospedagem excluída.');
            }
        }
        return $this->redirect('/admin/hostings');
    }
}

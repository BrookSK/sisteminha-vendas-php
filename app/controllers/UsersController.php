<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\User;

class UsersController extends Controller
{
    public function index()
    {
        $this->requireRole(['manager','admin']);
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $user = new User();
        $items = $user->paginate($limit, $offset, $q ?: null);
        $total = $user->countFiltered($q ?: null);

        $this->render('users/index', [
            'title' => 'Usuários',
            'items' => $items,
            'q' => $q,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function new()
    {
        $this->requireRole(['manager','admin']);
        // Supervisores (apenas vendedores e gerentes)
        $u = new \Models\User();
        $supervisors = $u->listByRoles(['seller','manager','admin']);
        $this->render('users/form', [
            'title' => 'Novo Usuário',
            'action' => '/admin/users/create',
            'user' => ['ativo' => 1],
            'roles' => ['seller' => 'Vendedor (registrar vendas)', 'organic' => 'Orgânico (lançar vendas sem comissão)', 'trainee' => 'Trainee (vendedor em treinamento)', 'manager' => 'Gerente (vendas e relatórios)', 'admin' => 'Admin (tudo)'],
            'supervisors' => $supervisors,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'seller');
        $ativo = (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0;
        if (!in_array($role, ['seller','organic','trainee','manager','admin'], true)) { $role = 'seller'; }
        if ($name === '' || $email === '' || $password === '') {
            return $this->render('users/form', [
                'title' => 'Novo Usuário',
                'action' => '/admin/users/create',
                'user' => ['name'=>$name,'email'=>$email,'role'=>$role,'ativo'=>$ativo],
                'roles' => ['seller' => 'Vendedor (registrar vendas)', 'organic' => 'Orgânico (lançar vendas sem comissão)', 'trainee' => 'Trainee (vendedor em treinamento)', 'manager' => 'Gerente (vendas e relatórios)', 'admin' => 'Admin (tudo)'],
                'supervisors' => (new \Models\User())->listByRoles(['seller','manager','admin']),
                'error' => 'Preencha nome, e-mail e senha.',
                '_csrf' => Auth::csrf(),
            ]);
        }
        try {
            $u = new User();
            $supervisorId = null;
            if ($role === 'trainee') {
                $sid = (int)($_POST['supervisor_user_id'] ?? 0);
                if ($sid > 0) { $supervisorId = $sid; }
            }
            $u->createWithRole($name, $email, $password, $role, $ativo, $supervisorId);
            return $this->redirect('/admin/users');
        } catch (\Throwable $e) {
            return $this->render('users/form', [
                'title' => 'Novo Usuário',
                'action' => '/admin/users/create',
                'user' => ['name'=>$name,'email'=>$email,'role'=>$role,'ativo'=>$ativo,'supervisor_user_id'=>(int)($_POST['supervisor_user_id'] ?? 0)],
                'roles' => ['seller' => 'Vendedor (registrar vendas)', 'organic' => 'Orgânico (lançar vendas sem comissão)', 'trainee' => 'Trainee (vendedor em treinamento)', 'manager' => 'Gerente (vendas e relatórios)', 'admin' => 'Admin (tudo)'],
                'supervisors' => (new \Models\User())->listByRoles(['seller','manager']),
                'error' => 'Erro ao criar usuário: ' . $e->getMessage(),
                '_csrf' => Auth::csrf(),
            ]);
        }
    }

    public function edit()
    {
        $this->requireRole(['manager','admin']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/users');
        $u = (new User())->findById($id);
        if (!$u) return $this->redirect('/admin/users');
        $uModel = new User();
        $this->render('users/form', [
            'title' => 'Editar Usuário',
            'action' => '/admin/users/update?id=' . $id,
            'user' => $u,
            'roles' => ['seller' => 'Vendedor (registrar vendas)', 'organic' => 'Orgânico (lançar vendas sem comissão)', 'trainee' => 'Trainee (vendedor em treinamento)', 'manager' => 'Gerente (vendas e relatórios)', 'admin' => 'Admin (tudo)'],
            'supervisors' => $uModel->listByRoles(['seller','manager','admin']),
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function update()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/users');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'seller');
        $ativo = (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0;
        if (!in_array($role, ['seller','organic','trainee','manager','admin'], true)) { $role = 'seller'; }
        if ($name === '' || $email === '') {
            return $this->render('users/form', [
                'title' => 'Editar Usuário',
                'action' => '/admin/users/update?id=' . $id,
                'user' => ['id'=>$id,'name'=>$name,'email'=>$email,'role'=>$role,'ativo'=>$ativo],
                'roles' => ['seller' => 'Vendedor (registrar vendas)', 'organic' => 'Orgânico (lançar vendas sem comissão)', 'trainee' => 'Trainee (vendedor em treinamento)', 'manager' => 'Gerente (vendas e relatórios)', 'admin' => 'Admin (tudo)'],
                'error' => 'Preencha nome e e-mail.',
                '_csrf' => Auth::csrf(),
            ]);
        }
        try {
            $u = new User();
            $supervisorId = null;
            if ($role === 'trainee') {
                $sid = (int)($_POST['supervisor_user_id'] ?? 0);
                if ($sid > 0) { $supervisorId = $sid; }
            }
            $u->updateUser($id, $name, $email, ($password === '' ? null : $password), $role, $ativo, $supervisorId);
            return $this->redirect('/admin/users');
        } catch (\Throwable $e) {
            return $this->render('users/form', [
                'title' => 'Editar Usuário',
                'action' => '/admin/users/update?id=' . $id,
                'user' => ['id'=>$id,'name'=>$name,'email'=>$email,'role'=>$role,'ativo'=>$ativo],
                'roles' => ['seller' => 'Vendedor (registrar vendas)', 'organic' => 'Orgânico (lançar vendas sem comissão)', 'trainee' => 'Trainee (vendedor em treinamento)', 'manager' => 'Gerente (vendas e relatórios)', 'admin' => 'Admin (tudo)'],
                'error' => 'Erro ao atualizar usuário: ' . $e->getMessage(),
                '_csrf' => Auth::csrf(),
            ]);
        }
    }

    public function delete()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // impede deletar a si mesmo
            if ((Auth::user()['id'] ?? 0) === $id) {
                return $this->redirect('/admin/users');
            }
            (new User())->delete($id);
        }
        return $this->redirect('/admin/users');
    }

    public function options()
    {
        $this->requireRole(['manager','admin']);
        $q = trim($_GET['q'] ?? '');
        $u = new User();
        $items = $q !== '' ? $u->paginate(50, 0, $q) : $u->allBasic();
        $out = array_map(function($r){
            return ['id'=>(int)$r['id'], 'text'=> (string)($r['name'] ?? $r['email'] ?? ('#'.$r['id']))];
        }, $items);
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }
}

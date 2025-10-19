<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Project;
use Models\Demand;
use Models\Project as ProjectModel;

class ProjectsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $user = Auth::user();
        $role = $user['role'] ?? 'seller';
        $uid = $user['id'] ?? null;

        $p = new Project();
        if ($role === 'admin') {
            $items = $p->list();
        } else {
            // não-admin: apenas projetos criados por ele
            $items = array_values(array_filter($p->list(500,0), function($r) use ($uid){ return (int)($r['created_by'] ?? 0) === (int)$uid; }));
        }
        $this->render('projects/index', [
            'title' => 'Projetos',
            'items' => $items,
        ]);
    }

    public function options()
    {
        $this->requireRole(['seller','manager','admin']);
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        $limit = (int)($_GET['limit'] ?? 10);
        if ($limit <= 0 || $limit > 50) { $limit = 10; }
        $items = (new ProjectModel())->options($q, $limit);
        echo json_encode(['items' => array_map(function($r){
            return [ 'id' => (int)$r['id'], 'name' => (string)$r['name'] ];
        }, $items)]);
        exit;
    }

    public function create()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'status' => $_POST['status'] ?? 'nao_iniciada',
            'start_date' => $_POST['start_date'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'description' => $_POST['description'] ?? null,
        ];
        if ($d['name'] === '' || empty($d['start_date']) || empty($d['due_date'])) {
            return $this->redirect('/admin/projects');
        }
        (new Project())->create($d, $u['id'] ?? null);
        return $this->redirect('/admin/projects');
    }

    public function update()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/projects');
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'status' => $_POST['status'] ?? 'nao_iniciada',
            'start_date' => $_POST['start_date'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'description' => $_POST['description'] ?? null,
        ];
        // Permissão: não-admin só edita projeto que criou
        $p = (new Project())->find($id);
        if (!$p) return $this->redirect('/admin/projects');
        if (($u['role'] ?? 'seller') !== 'admin' && (int)($p['created_by'] ?? 0) !== (int)($u['id'] ?? -1)) {
            return $this->redirect('/admin/projects');
        }
        (new Project())->updateRow($id, $d);
        return $this->redirect('/admin/projects');
    }

    public function view()
    {
        $this->requireRole(['seller','manager','admin']);
        $u = Auth::user();
        $id = (int)($_GET['id'] ?? 0);
        $p = (new Project())->find($id);
        if (!$p) return $this->redirect('/admin/projects');
        if (($u['role'] ?? 'seller') !== 'admin' && (int)($p['created_by'] ?? 0) !== (int)($u['id'] ?? -1)) {
            return $this->redirect('/admin/projects');
        }
        $demands = (new Project())->relatedDemands($id);
        $this->render('projects/view', [
            'title' => 'Projeto: '.$p['name'],
            'project' => $p,
            'demands' => $demands,
        ]);
    }
}

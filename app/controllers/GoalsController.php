<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Goal;
use Models\GoalAssignment;
use Models\Notification;

class GoalsController extends Controller
{
    public function index()
    {
        $this->requireRole(['manager','admin']);
        $goals = (new Goal())->list(200,0);
        $this->render('goals/index', [
            'title' => 'Metas e Previsões',
            'goals' => $goals,
            '_csrf' => \Core\Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $in = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'tipo' => $_POST['tipo'] ?? 'global',
            'valor_meta' => (float)($_POST['valor_meta'] ?? 0),
            'moeda' => $_POST['moeda'] ?? 'USD',
            'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-01'),
            'data_fim' => $_POST['data_fim'] ?? date('Y-m-t'),
        ];
        $id = (new Goal())->create($in, Auth::user()['id'] ?? null);
        // Notificar vendedores (simples): todos ativos
        $this->notifyAll("Nova meta: {$in['titulo']}", 'Uma nova meta foi criada. Verifique seu painel.');
        return $this->redirect('/admin/goals');
    }

    public function update()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id<=0) return $this->redirect('/admin/goals');
        $in = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'tipo' => $_POST['tipo'] ?? 'global',
            'valor_meta' => (float)($_POST['valor_meta'] ?? 0),
            'moeda' => $_POST['moeda'] ?? 'USD',
            'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-01'),
            'data_fim' => $_POST['data_fim'] ?? date('Y-m-t'),
        ];
        (new Goal())->updateRow($id, $in);
        $this->notifyAll("Meta atualizada: {$in['titulo']}", 'Uma meta foi atualizada. Verifique seu painel.');
        return $this->redirect('/admin/goals');
    }

    public function delete()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id>0) (new Goal())->deleteRow($id);
        return $this->redirect('/admin/goals');
    }

    private function notifyAll(string $title, string $message): void
    {
        // notifica todos os usuários ativos
        $db = \Core\Database::pdo(); // obter PDO da camada de DB
        $rows = $db->query("SELECT id FROM usuarios WHERE ativo=1")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $userIds = array_map(function($r){ return (int)($r['id'] ?? 0); }, $rows);
        $userIds = array_values(array_filter($userIds, fn($v)=>$v>0));
        if (!empty($userIds)) {
            $notif = new Notification();
            $createdBy = (int)(Auth::user()['id'] ?? 0);
            $notif->createWithUsers($createdBy, $title, $message, 'meta', 'new', $userIds);
        }
    }
}

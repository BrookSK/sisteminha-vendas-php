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
        $goalModel = new Goal();
        $assign = new GoalAssignment();
        $goals = $goalModel->list(200,0);
        // Atualiza progresso atual de cada vendedor por meta e agrega totais do período
        $dashMeta = 0.0; $dashReal = 0.0; $dashPrev = 0.0; $dashDiasTot = 0; $dashDiasPass = 0;
        foreach ($goals as $g) {
            $from = (string)($g['data_inicio'] ?? date('Y-m-01'));
            $to = (string)($g['data_fim'] ?? date('Y-m-t'));
            $diasTotais = max(1, (strtotime($to) - strtotime($from)) / 86400 + 1);
            $diasPassados = max(1, (min(time(), strtotime($to)) - strtotime($from)) / 86400 + 1);
            $rows = $assign->listByGoal((int)$g['id']);
            foreach ($rows as $r) {
                $real = $goalModel->salesTotalUsd($from, $to, (int)$r['id_vendedor']);
                $assign->updateProgress((int)$g['id'], (int)$r['id_vendedor'], (float)$real);
                $dashMeta += (float)($r['valor_meta'] ?? 0);
                $dashReal += (float)$real;
                // previsão individual: média diária x dias totais
                $media = $diasPassados > 0 ? ($real / $diasPassados) : 0.0;
                $dashPrev += $media * $diasTotais;
            }
            $dashDiasTot += (int)$diasTotais;
            $dashDiasPass += (int)$diasPassados;
        }
        $this->render('goals/index', [
            'title' => 'Metas e Previsões',
            'goals' => $goals,
            'dash_meta' => $dashMeta,
            'dash_real' => $dashReal,
            'dash_prev' => $dashPrev,
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

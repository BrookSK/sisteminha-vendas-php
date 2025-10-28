<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Goal;
use Models\GoalAssignment;
use Models\Setting;
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
            if (($g['tipo'] ?? 'global') === 'global') {
                // contabiliza meta global mesmo sem atribuições
                $realTotal = $goalModel->salesTotalUsd($from, $to, null);
                $dashMeta += (float)($g['valor_meta'] ?? 0);
                $dashReal += (float)$realTotal;
                $mediaG = $diasPassados > 0 ? ($realTotal / $diasPassados) : 0.0;
                $dashPrev += $mediaG * $diasTotais;
            } else {
                // garantir ao menos uma atribuição ao criador se estiver faltando
                $rows = $assign->listByGoal((int)$g['id']);
                if (empty($rows)) {
                    $creatorId = (int)($g['criado_por'] ?? 0);
                    if ($creatorId > 0) {
                        $assign->upsert((int)$g['id'], $creatorId, (float)($g['valor_meta'] ?? 0));
                        $rows = $assign->listByGoal((int)$g['id']);
                    }
                }
                foreach ($rows as $r) {
                    $real = $goalModel->salesTotalUsd($from, $to, (int)$r['id_vendedor']);
                    $assign->updateProgress((int)$g['id'], (int)$r['id_vendedor'], (float)$real);
                    $dashMeta += (float)($r['valor_meta'] ?? 0);
                    $dashReal += (float)$real;
                    // previsão individual: média diária x dias totais
                    $media = $diasPassados > 0 ? ($real / $diasPassados) : 0.0;
                    $dashPrev += $media * $diasTotais;
                }
            }
            $dashDiasTot += (int)$diasTotais;
            $dashDiasPass += (int)$diasPassados;
        }
        // Período padrão do sistema (10->9 ou configurado)
        try { [$defFrom,$defTo] = (new Setting())->currentPeriod(); } catch (\Throwable $e) { $defFrom = date('Y-m-10'); $defTo = date('Y-m-09', strtotime('first day of next month')); }

        $this->render('goals/index', [
            'title' => 'Metas e Previsões',
            'goals' => $goals,
            'dash_meta' => $dashMeta,
            'dash_real' => $dashReal,
            'dash_prev' => $dashPrev,
            '_csrf' => \Core\Auth::csrf(),
            'users' => (function(){
                try {
                    $db = \Core\Database::pdo();
                    return $db->query("SELECT id, name, role, ativo FROM usuarios ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                } catch (\Throwable $e) { return []; }
            })(),
            'default_from' => $defFrom,
            'default_to' => $defTo,
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
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'data_fim' => $_POST['data_fim'] ?? null,
        ];
        // Default period: use system-wide currentPeriod when not provided
        if (empty($in['data_inicio']) || empty($in['data_fim'])) {
            try { [$from,$to] = (new Setting())->currentPeriod(); } catch (\Throwable $e) { $from = date('Y-m-01'); $to = date('Y-m-t'); }
            if (empty($in['data_inicio'])) $in['data_inicio'] = $from;
            if (empty($in['data_fim'])) $in['data_fim'] = $to;
        }
        $creatorId = (int)(Auth::user()['id'] ?? 0);
        $id = (new Goal())->create($in, $creatorId);
        // Se tipo individual: atribui automaticamente a todos ativos (seller/trainee/manager)
        if (($in['tipo'] ?? 'global') === 'individual') {
            try {
                $db = \Core\Database::pdo();
                $rows = $db->query("SELECT id, role, ativo FROM usuarios WHERE ativo=1")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $u) {
                    $role = $u['role'] ?? '';
                    if (in_array($role, ['seller','trainee','manager'], true)) {
                        (new GoalAssignment())->upsert((int)$id, (int)$u['id'], (float)($in['valor_meta'] ?? 0));
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
        }
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
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'data_fim' => $_POST['data_fim'] ?? null,
        ];
        if (empty($in['data_inicio']) || empty($in['data_fim'])) {
            try { [$from,$to] = (new Setting())->currentPeriod(); } catch (\Throwable $e) { $from = date('Y-m-01'); $to = date('Y-m-t'); }
            if (empty($in['data_inicio'])) $in['data_inicio'] = $from;
            if (empty($in['data_fim'])) $in['data_fim'] = $to;
        }
        (new Goal())->updateRow($id, $in);
        // Se tipo individual: garantir/atualizar atribuições automáticas para todos ativos
        try {
            $g = (new Goal())->find($id);
            if (($g['tipo'] ?? 'global') === 'individual') {
                $db = \Core\Database::pdo();
                $rows = $db->query("SELECT id, role, ativo FROM usuarios WHERE ativo=1")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $u) {
                    $role = $u['role'] ?? '';
                    if (in_array($role, ['seller','trainee','manager'], true)) {
                        (new GoalAssignment())->upsert($id, (int)$u['id'], (float)($g['valor_meta'] ?? 0));
                    }
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
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

    public function assign()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $goalId = (int)($_POST['goal_id'] ?? 0);
        $sellerId = (int)($_POST['seller_id'] ?? 0);
        $valor = (float)($_POST['valor_meta'] ?? 0);
        if ($goalId>0 && $sellerId>0) {
            (new GoalAssignment())->upsert($goalId, $sellerId, $valor);
        }
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

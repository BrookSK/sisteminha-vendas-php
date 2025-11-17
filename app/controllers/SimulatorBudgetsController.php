<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\SimulatorBudget;

class SimulatorBudgetsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $me = Auth::user();
        $userId = (int)($me['id'] ?? 0);
        if ($userId <= 0) { return $this->redirect('/login'); }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, min(100, (int)($_GET['per'] ?? 20)));
        $offset = ($page - 1) * $per;
        $model = new SimulatorBudget();
        $items = $model->listForUser($userId, $per, $offset);
        $total = $model->countForUser($userId);
        $this->render('sales_simulator/budgets', [
            'title' => 'Meus Orçamentos do Simulador',
            'items' => $items,
            'page' => $page,
            'per' => $per,
            'total' => $total,
        ]);
    }

    public function save()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $me = Auth::user();
        $userId = (int)($me['id'] ?? 0);
        if ($userId <= 0) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'not_auth']); return; }
        $name = trim($_POST['name'] ?? '');
        $payloadRaw = $_POST['payload'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $payload = json_decode($payloadRaw, true);
        if ($name === '' || !is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'error'=>'invalid']);
            return;
        }
        $model = new SimulatorBudget();
        if ($id > 0) {
            $model->updateForUser($id, $userId, $name, $payload);
            $savedId = $id;
        } else {
            $savedId = $model->createForUser($userId, $name, $payload);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'id'=>$savedId]);
    }

    public function duplicate()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $me = Auth::user();
        $userId = (int)($me['id'] ?? 0);
        if ($userId <= 0) { return $this->redirect('/login'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { return $this->redirect('/admin/sales-simulator/budgets'); }
        $model = new SimulatorBudget();
        $newId = $model->duplicateForUser($id, $userId, null);
        if ($newId === null) {
            $this->flash('danger', 'Não foi possível duplicar o orçamento.');
        } else {
            $this->flash('success', 'Orçamento duplicado com sucesso.');
        }
        return $this->redirect('/admin/sales-simulator/budgets');
    }

    public function delete()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $me = Auth::user();
        $userId = (int)($me['id'] ?? 0);
        if ($userId <= 0) { return $this->redirect('/login'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new SimulatorBudget())->deleteForUser($id, $userId);
            $this->flash('success', 'Orçamento excluído com sucesso.');
        }
        return $this->redirect('/admin/sales-simulator/budgets');
    }
}

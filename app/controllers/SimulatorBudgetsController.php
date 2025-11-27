<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\SimulatorBudget;
use Models\SimulatorProductPurchase;

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

        // Marca orçamentos que já têm produtos comprados no relatório consolidado,
        // para impedir que sejam revertidos de "pago" para "não pago".
        if ($items) {
            $purchaseModel = new SimulatorProductPurchase();
            foreach ($items as &$b) {
                $b['locked_paid'] = false;
                $bid = (int)($b['id'] ?? 0);
                if ($bid <= 0) continue;
                $full = $model->findForUser($bid, $userId);
                if (!$full) continue;
                $data = json_decode($full['data_json'] ?? '[]', true) ?: [];
                $its = $data['items'] ?? [];
                if (!is_array($its) || !$its) continue;
                $keysMap = [];
                foreach ($its as $it) {
                    $name = trim((string)($it['nome'] ?? ''));
                    if ($name === '') continue;
                    $productId = $it['product_id'] ?? null;
                    if ($productId) {
                        $key = 'db:'.(int)$productId;
                    } else {
                        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)), 'UTF-8');
                        $key = 'free:'.$norm;
                    }
                    $keysMap[$key] = true;
                }
                $keys = array_keys($keysMap);
                if (!$keys) continue;
                $purchased = $purchaseModel->getForKeys($keys);
                foreach ($purchased as $qty) {
                    if ((int)$qty > 0) {
                        $b['locked_paid'] = true;
                        break;
                    }
                }
            }
            unset($b);
        }
        $total = $model->countForUser($userId);
        $totalPages = ceil($total / $per);
        $this->render('sales_simulator/budgets', [
            'title' => 'Meus Orçamentos do Simulador',
            'items' => $items,
            'page' => $page,
            'per' => $per,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    public function save()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $me = Auth::user();
        $userId = (int)($me['id'] ?? 0);
        $role = (string)($me['role'] ?? 'seller');
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
        $paidRaw = $_POST['paid'] ?? null;
        $paid = $paidRaw === '1' || $paidRaw === 'true' || $paidRaw === 'on';
        if ($id > 0) {
            if (in_array($role, ['admin','manager'], true)) {
                // Admins/gerentes podem editar qualquer orçamento por ID
                $model->updateById($id, $name, $payload);
            } else {
                $model->updateForUser($id, $userId, $name, $payload);
            }
            $savedId = $id;
        } else {
            // Criação sempre fica atrelada ao usuário logado
            $savedId = $model->createForUser($userId, $name, $payload);
        }
        if ($savedId > 0) {
            // Deixa o modelo definir a data de pagamento automaticamente (NOW) quando marcar como pago
            if (in_array($role, ['admin','manager'], true)) {
                $model->setPaidById($savedId, $paid, null);
            } else {
                $model->setPaidForUser($savedId, $userId, $paid, null);
            }
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

    public function togglePaid()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $this->csrfCheck();
        $me = Auth::user();
        $userId = (int)($me['id'] ?? 0);
        if ($userId <= 0) { return $this->redirect('/login'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { return $this->redirect('/admin/sales-simulator/budgets'); }
        $paid = isset($_POST['paid']) ? (bool)$_POST['paid'] : false;
        $model = new SimulatorBudget();
        // Sem data manual: modelo usa NOW quando pago=true e limpa quando pago=false
        $model->setPaidForUser($id, $userId, $paid, null);
        $this->flash('success', $paid ? 'Orçamento marcado como pago.' : 'Marcação de pagamento removida.');
        return $this->redirect('/admin/sales-simulator/budgets');
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Setting;
use Models\SimulatorBudget;

class SalesSimulatorController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        try { $rate = (float)((new Setting())->get('usd_rate', '5.83')); } catch (\Throwable $e) { $rate = 5.83; }
        $budgetData = null;
        $budgetName = '';
        $budgetPaid = false;
        $budgetPaidAt = null;
        $budgetId = (int)($_GET['budget_id'] ?? 0);
        if ($budgetId > 0) {
            $me = Auth::user();
            $userId = (int)($me['id'] ?? 0);
            $role = (string)($me['role'] ?? 'seller');
            if ($userId > 0) {
                try {
                    $model = new SimulatorBudget();
                    // Primeiro tenta carregar apenas se pertencer ao usuário logado
                    $row = $model->findForUser($budgetId, $userId);
                    // Se não encontrar e for admin/manager, permite abrir qualquer orçamento pelo ID
                    if (!$row && in_array($role, ['admin','manager'], true)) {
                        $row = $model->findById($budgetId);
                    }
                    if ($row) {
                        $budgetName = (string)($row['name'] ?? '');
                        $budgetPaid = (bool)($row['paid'] ?? false);
                        $budgetPaidAt = $row['paid_at'] ?? null;
                        $decoded = json_decode($row['data_json'] ?? '[]', true);
                        if (is_array($decoded)) { $budgetData = $decoded; }
                    }
                } catch (\Throwable $e) {
                    $budgetData = null;
                }
            }
        }
        $this->render('sales_simulator/index', [
            'title' => 'Simulador de Cálculo',
            'usd_rate' => $rate,
            'budget_data' => $budgetData,
            'budget_id' => $budgetId,
            'budget_name' => $budgetName,
            'budget_paid' => $budgetPaid,
            'budget_paid_at' => $budgetPaidAt,
        ]);
    }
}

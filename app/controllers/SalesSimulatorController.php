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
        $budgetId = (int)($_GET['budget_id'] ?? 0);
        if ($budgetId > 0) {
            $me = Auth::user();
            $userId = (int)($me['id'] ?? 0);
            if ($userId > 0) {
                try {
                    $model = new SimulatorBudget();
                    $row = $model->findForUser($budgetId, $userId);
                    if ($row) {
                        $budgetName = (string)($row['name'] ?? '');
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
        ]);
    }
}

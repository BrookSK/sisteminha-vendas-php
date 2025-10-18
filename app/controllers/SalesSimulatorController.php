<?php
namespace Controllers;

use Core\Controller;
use Models\Setting;

class SalesSimulatorController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        try { $rate = (float)((new Setting())->get('usd_rate', '5.83')); } catch (\Throwable $e) { $rate = 5.83; }
        $this->render('sales_simulator/index', [
            'title' => 'Simulador de Cálculo',
            'usd_rate' => $rate,
        ]);
    }
}

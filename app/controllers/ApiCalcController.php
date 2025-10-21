<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Setting;
use Models\Cost;

class ApiCalcController extends Controller
{
    public function settings()
    {
        $this->requireRole(['admin']);
        $set = new Setting();
        $token = (string)$set->get('api_calc_token', '');
        $incUsd = (int)$set->get('api_calc_include_usd', '1') === '0' ? 0 : 1;
        $incBrl = (int)$set->get('api_calc_include_brl', '1') === '0' ? 0 : 1;
        $incPct = (int)$set->get('api_calc_include_percent', '1') === '0' ? 0 : 1;
        $this->render('api_calc/settings', [
            'title' => 'API: Cálculo de Líquido',
            'token' => $token,
            'include_usd' => $incUsd,
            'include_brl' => $incBrl,
            'include_percent' => $incPct,
            'usd_rate' => (float)$set->get('usd_rate', '5.83'),
        ]);
    }

    public function save()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $set = new Setting();
        $token = trim($_POST['api_calc_token'] ?? '');
        $incUsd = isset($_POST['api_calc_include_usd']) ? 1 : 0;
        $incBrl = isset($_POST['api_calc_include_brl']) ? 1 : 0;
        $incPct = isset($_POST['api_calc_include_percent']) ? 1 : 0;
        if ($token !== '') { $set->set('api_calc_token', $token); }
        $set->set('api_calc_include_usd', (string)$incUsd);
        $set->set('api_calc_include_brl', (string)$incBrl);
        $set->set('api_calc_include_percent', (string)$incPct);
        $this->redirect('/admin/api-calc');
    }

    public function compute()
    {
        // Sem autenticação: aceita JSON body, form-data ou query string
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $gross = 0.0; $detail = false;
        $raw = file_get_contents('php://input') ?: '';
        $in = json_decode($raw, true);
        if (is_array($in)) {
            $gross = (float)($in['gross_usd'] ?? $in['gross'] ?? 0);
            $detail = (bool)($in['detail'] ?? false);
        }
        // Fallbacks via form/query
        if ($gross <= 0) {
            if ($method === 'POST') {
                $gross = (float)($_POST['gross_usd'] ?? $_POST['gross'] ?? 0);
                $detail = isset($_POST['detail']) ? (bool)$_POST['detail'] : $detail;
            }
            if ($gross <= 0) {
                $gross = (float)($_GET['gross_usd'] ?? $_GET['gross'] ?? 0);
                $detail = isset($_GET['detail']) ? (bool)$_GET['detail'] : $detail;
            }
        }
        $set = new Setting();
        $rate = (float)$set->get('usd_rate', '5.83'); if ($rate <= 0) $rate = 5.83;
        $incUsd = ((int)$set->get('api_calc_include_usd', '1')) === 1;
        $incBrl = ((int)$set->get('api_calc_include_brl', '1')) === 1;
        $incPct = ((int)$set->get('api_calc_include_percent', '1')) === 1;

        $sums = (new Cost())->globalSums();
        $fixedUsd = $incUsd ? (float)($sums['fixed_usd'] ?? 0) : 0.0;
        $fixedBrl = $incBrl ? (float)($sums['fixed_brl'] ?? 0) : 0.0;
        $fixedUsdFromBrl = $incBrl ? ($rate > 0 ? ($fixedBrl / $rate) : 0.0) : 0.0;
        $percent = $incPct ? (float)($sums['percent'] ?? 0) : 0.0; // sum of percentage points

        $percentDeduction = ($percent > 0) ? ($gross * ($percent / 100.0)) : 0.0;
        $totalDeductions = $fixedUsd + $fixedUsdFromBrl + $percentDeduction;
        $net = $gross - $totalDeductions;

        header('Content-Type: application/json');
        $out = [
            'gross_usd' => round($gross, 2),
            'net_usd' => round($net, 2),
        ];
        if ($detail) {
            $out['deductions'] = [
                'fixed_usd' => round($fixedUsd, 2),
                'fixed_brl_converted_to_usd' => round($fixedUsdFromBrl, 2),
                'percent_total_points' => round($percent, 4),
                'percent_deduction_usd' => round($percentDeduction, 2),
                'usd_rate_used' => $rate,
            ];
        }
        echo json_encode($out);
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Setting;
use Models\Log;

class SettingsController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        $embalagem = (float)$setting->get('embalagem_usd_por_kg', '9.70');
        $costRate = (float)$setting->get('cost_rate', '0.15');
        $sessionLifetime = (int)$setting->get('session_lifetime', '28800');
        $lbsPerKg = (float)$setting->get('lbs_per_kg', '2.2');
        $periodStart = (string)$setting->get('current_period_start', '');
        $periodEnd = (string)$setting->get('current_period_end', '');

        $this->render('settings/index', [
            'title' => 'Configurações',
            'rate' => $rate,
            'embalagem' => $embalagem,
            'cost_rate' => $costRate,
            'session_lifetime' => $sessionLifetime,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'lbs_per_kg' => $lbsPerKg,
            // webhooks
            'webhook_containers_url' => (string)$setting->get('webhook_containers_url', ''),
            'webhook_sales_url' => (string)$setting->get('webhook_sales_url', ''),
            'webhook_secret_token' => (string)$setting->get('webhook_secret_token', ''),
        ]);
    }

    public function save()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $rate = (float)($_POST['usd_rate'] ?? 5.83);
        if ($rate <= 0) $rate = 5.83;
        $embalagem = (float)($_POST['embalagem_usd_por_kg'] ?? 9.70);
        if ($embalagem < 0) $embalagem = 0;
        $costRate = (float)($_POST['cost_rate'] ?? 0.15);
        if ($costRate < 0) $costRate = 0; if ($costRate > 1) $costRate = 1;
        $sessionLifetime = (int)($_POST['session_lifetime'] ?? 28800);
        if ($sessionLifetime < 1800) $sessionLifetime = 1800; // mínimo 30 min
        $periodStart = trim($_POST['current_period_start'] ?? '');
        $periodEnd = trim($_POST['current_period_end'] ?? '');
        $lbsPerKg = (float)($_POST['lbs_per_kg'] ?? 2.2);
        if ($lbsPerKg <= 0) $lbsPerKg = 2.2;
        $setting = new Setting();
        $setting->set('usd_rate', (string)$rate);
        $setting->set('embalagem_usd_por_kg', (string)$embalagem);
        $setting->set('cost_rate', (string)$costRate);
        $setting->set('session_lifetime', (string)$sessionLifetime);
        if ($periodStart !== '' && $periodEnd !== '') {
            $setting->set('current_period_start', $periodStart);
            $setting->set('current_period_end', $periodEnd);
        }
        $setting->set('lbs_per_kg', (string)$lbsPerKg);
        // webhooks
        $wc = trim($_POST['webhook_containers_url'] ?? '');
        $ws = trim($_POST['webhook_sales_url'] ?? '');
        $wt = trim($_POST['webhook_secret_token'] ?? '');
        $setting->set('webhook_containers_url', $wc);
        $setting->set('webhook_sales_url', $ws);
        if ($wt !== '') { $setting->set('webhook_secret_token', $wt); }
        (new Log())->add(
            Auth::user()['id'] ?? null,
            'settings',
            'update',
            null,
            json_encode([
                'usd_rate' => $rate,
                'embalagem_usd_por_kg' => $embalagem,
                'cost_rate' => $costRate,
                'session_lifetime' => $sessionLifetime,
                'current_period_start'=>$periodStart,
                'current_period_end'=>$periodEnd,
                'lbs_per_kg'=>$lbsPerKg,
                'webhook_containers_url'=>$wc,
                'webhook_sales_url'=>$ws,
            ])
        );
        $this->redirect('/admin/settings');
    }

    public function calculations()
    {
        $this->requireRole(['admin']);
        $set = new Setting();
        $rate = (float)$set->get('usd_rate', '5.83');
        $costRate = (float)$set->get('cost_rate', '0.15');
        $this->render('settings/calculations', [
            'title' => 'Cálculo das Comissões e Custos',
            'rate' => $rate,
            'cost_rate' => $costRate,
        ]);
    }

    public function calculationsSimple()
    {
        $this->requireRole(['seller','manager','admin']);
        $set = new Setting();
        $rate = (float)$set->get('usd_rate', '5.83');
        $costRate = (float)$set->get('cost_rate', '0.15');
        $this->render('settings/calculations_simple', [
            'title' => 'Entenda os Cálculos (versão simples)',
            'rate' => $rate,
            'cost_rate' => $costRate,
        ]);
    }
}

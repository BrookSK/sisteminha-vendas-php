<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Cost;
use Models\Log;
use Models\CostsRecurrence;
use Models\Setting;

class CostsController extends Controller
{
    public function index()
    {
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        $cost = new Cost();
        $items = $cost->list(100, 0, $from ?: null, $to ?: null);
        $this->render('costs/index', [
            'title' => 'Custos',
            'items' => $items,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create()
    {
        $this->csrfCheck();
        $date = $_POST['data'] ?: date('Y-m-d');
        $cat = trim($_POST['categoria'] ?? 'geral');
        $desc = trim($_POST['descricao'] ?? '');
        $valType = $_POST['valor_tipo'] ?? 'usd';
        $inputUsd = (float)($_POST['valor_usd'] ?? 0);
        $inputBrl = (float)($_POST['valor_brl'] ?? 0);
        $inputPct = isset($_POST['valor_percent']) ? (float)$_POST['valor_percent'] : null;
        $rate = (float)((new Setting())->get('usd_rate', '5.83'));
        if ($rate <= 0) $rate = 5.83;
        $val = 0.0;
        if ($valType === 'usd') { $val = $inputUsd; }
        elseif ($valType === 'brl') { $val = $rate>0 ? ($inputBrl / $rate) : 0.0; }
        elseif ($valType === 'percent') { $val = 0.0; }
        $userRole = Auth::user()['role'] ?? 'seller';
        $recType = $_POST['recorrente_tipo'] ?? 'none';
        $recActive = (int)($_POST['recorrente_ativo'] ?? 0) === 1 ? 1 : 0;
        $parcelasTotal = isset($_POST['parcelas_total']) ? (int)$_POST['parcelas_total'] : null;

        // Only admin/manager may configure recurrence
        $canRec = in_array($userRole, ['admin','manager'], true);
        if (!$canRec) { $recType = 'none'; $recActive = 0; $parcelasTotal = null; }

        $costModel = new Cost();
        if ($recType === 'none') {
            $id = $costModel->createFull([
                'data' => $date,
                'categoria' => $cat,
                'descricao' => $desc,
                'valor_usd' => $val,
                'valor_tipo' => $valType,
                'valor_brl' => ($valType==='brl'?$inputBrl:null),
                'valor_percent' => ($valType==='percent'?$inputPct:null),
                'recorrente_tipo' => 'none',
                'recorrente_ativo' => 0,
                'recorrente_proxima_data' => null,
                'parcelas_total' => null,
                'parcela_atual' => null,
            ]);
        } else {
            // compute next occurrence date
            $next = null;
            if ($recType === 'weekly') { $next = date('Y-m-d', strtotime($date.' +7 days')); }
            elseif ($recType === 'monthly') { $next = CostsRecurrence::addMonthSafe($date); }
            elseif ($recType === 'yearly') { $next = date('Y-m-d', strtotime($date.' +1 year')); }
            elseif ($recType === 'installments') { $next = CostsRecurrence::addMonthSafe($date); }
            $payload = [
                'data' => $date,
                'categoria' => $cat,
                'descricao' => $desc,
                'valor_usd' => $val,
                'valor_tipo' => $valType,
                'valor_brl' => ($valType==='brl'?$inputBrl:null),
                'valor_percent' => ($valType==='percent'?$inputPct:null),
                'recorrente_tipo' => $recType,
                'recorrente_ativo' => $recActive,
                'recorrente_proxima_data' => $recActive ? $next : null,
                'parcelas_total' => $recType === 'installments' ? ($parcelasTotal ?: 1) : null,
                'parcela_atual' => $recType === 'installments' ? 1 : null,
            ];
            $id = $costModel->createFull($payload);
        }
        (new Log())->add(Auth::user()['id'] ?? null, 'custos', 'create', $id, json_encode(['data'=>$date,'categoria'=>$cat,'valor_usd'=>$val]));
        $this->redirect('/admin/costs');
    }

    public function delete()
    {
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Cost())->delete($id);
            (new Log())->add(Auth::user()['id'] ?? null, 'custos', 'delete', $id, null);
        }
        $this->redirect('/admin/costs');
    }

    public function exportCsv()
    {
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        $items = (new Cost())->list(10000, 0, $from ?: null, $to ?: null);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="custos.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['data','categoria','descricao','valor_usd','recorrente_tipo','recorrente_ativo','recorrente_proxima_data','parcelas_total','parcela_atual','created_at']);
        foreach ($items as $i) {
            fputcsv($out, [
                $i['data'],
                $i['categoria'],
                $i['descricao'] ?? '',
                $i['valor_usd'],
                $i['recorrente_tipo'] ?? 'none',
                (int)($i['recorrente_ativo'] ?? 0),
                $i['recorrente_proxima_data'] ?? '',
                $i['parcelas_total'] ?? '',
                $i['parcela_atual'] ?? '',
                $i['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    /** Admin/Manager: run due recurrences and generate pending costs. */
    public function runRecurrence()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $runner = new CostsRecurrence();
        $count = $runner->runDue(date('Y-m-d'));
        (new Log())->add(Auth::user()['id'] ?? null, 'custos', 'recurrence_run', null, json_encode(['generated'=>$count]));
        $this->redirect('/admin/costs');
    }
}

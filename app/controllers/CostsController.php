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
        $this->requireRole(['admin']);
        // Auto-run recurrences when opening the Costs page (admin only)
        try {
            $runner = new CostsRecurrence();
            $runner->runDue(date('Y-m-d'));
        } catch (\Throwable $e) {
            // ignore errors to not block the page
        }
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        // Default to current 10->09 period if no explicit filter
        if ($from === '' || $to === '') {
            try { $set = new Setting(); [$defFrom,$defTo] = $set->currentPeriod(); }
            catch (\Throwable $e) { $defFrom = ''; $defTo = ''; }
            if ($from === '' && $defFrom !== '') { $from = $defFrom; }
            if ($to === '' && $defTo !== '') { $to = $defTo; }
        }
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
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $date = $_POST['data'] ?: date('Y-m-d');
        $cat = trim($_POST['categoria'] ?? 'geral');
        $desc = trim($_POST['descricao'] ?? '');
        $valType = $_POST['valor_tipo'] ?? 'usd';
        $norm = function($v){ if ($v===null) return null; if (is_string($v)) { $v = str_replace(['.',' ,',' '],['','.',''], $v); $v = str_replace(',','.', $v); } return (float)$v; };
        $inputUsd = $norm($_POST['valor_usd'] ?? 0);
        $inputBrl = $norm($_POST['valor_brl'] ?? 0);
        $inputPct = isset($_POST['valor_percent']) ? $norm($_POST['valor_percent']) : null;
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
        $alignToPeriod = (int)($_POST['align_period'] ?? 0) === 1; // alinhar ao período 10->09

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
            if ($recType === 'weekly') {
                $next = date('Y-m-d', strtotime($date.' +7 days'));
            } elseif ($recType === 'monthly') {
                if ($alignToPeriod) {
                    // Próxima data = próximo dia 10 a partir de $date (inclusive)
                    $ref = new \DateTime($date);
                    $ten = (clone $ref)->setDate((int)$ref->format('Y'), (int)$ref->format('n'), 10);
                    if ($ref > $ten) {
                        // move to next month 10th
                        $firstNext = (clone $ref)->modify('first day of next month');
                        $ten = $firstNext->setDate((int)$firstNext->format('Y'), (int)$firstNext->format('n'), 10);
                    }
                    $next = $ten->format('Y-m-d');
                } else {
                    $next = CostsRecurrence::addMonthSafe($date);
                }
            } elseif ($recType === 'yearly') {
                $next = date('Y-m-d', strtotime($date.' +1 year'));
            } elseif ($recType === 'installments') {
                if ($alignToPeriod) {
                    $ref = new \DateTime($date);
                    $ten = (clone $ref)->setDate((int)$ref->format('Y'), (int)$ref->format('n'), 10);
                    if ($ref > $ten) {
                        $firstNext = (clone $ref)->modify('first day of next month');
                        $ten = $firstNext->setDate((int)$firstNext->format('Y'), (int)$firstNext->format('n'), 10);
                    }
                    $next = $ten->format('Y-m-d');
                } else {
                    $next = CostsRecurrence::addMonthSafe($date);
                }
            }
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
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Cost())->delete($id);
            (new Log())->add(Auth::user()['id'] ?? null, 'custos', 'delete', $id, null);
        }
        $this->redirect('/admin/costs');
    }

    public function update()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { return $this->redirect('/admin/costs'); }
        $date = $_POST['data'] ?: date('Y-m-d');
        $cat = trim($_POST['categoria'] ?? 'geral');
        $desc = trim($_POST['descricao'] ?? '');
        $valType = $_POST['valor_tipo'] ?? 'usd';
        $norm = function($v){ if ($v===null) return null; if (is_string($v)) { $v = str_replace(['.',' ,',' '],["",".",""], $v); $v = str_replace(',', '.', $v); } return (float)$v; };
        $inputUsd = $norm($_POST['valor_usd'] ?? 0);
        $inputBrl = $norm($_POST['valor_brl'] ?? 0);
        $inputPct = isset($_POST['valor_percent']) ? $norm($_POST['valor_percent']) : null;
        $rate = (float)((new Setting())->get('usd_rate', '5.83'));
        if ($rate <= 0) $rate = 5.83;
        $val = 0.0;
        if ($valType === 'usd') { $val = $inputUsd; }
        elseif ($valType === 'brl') { $val = $rate>0 ? ($inputBrl / $rate) : 0.0; }
        elseif ($valType === 'percent') { $val = 0.0; }
        (new Cost())->updateFull($id, [
            'data' => $date,
            'categoria' => $cat,
            'descricao' => $desc,
            'valor_usd' => $val,
            'valor_tipo' => $valType,
            'valor_brl' => ($valType==='brl'?$inputBrl:null),
            'valor_percent' => ($valType==='percent'?$inputPct:null),
        ]);
        // Handle recurrence updates
        $recType = $_POST['recorrente_tipo'] ?? null;
        if ($recType !== null) {
            $recActive = (int)($_POST['recorrente_ativo'] ?? 0) === 1 ? 1 : 0;
            $parcelasTotal = isset($_POST['parcelas_total']) ? (int)$_POST['parcelas_total'] : null;
            $alignToPeriod = (int)($_POST['align_period'] ?? 0) === 1;
            $next = null;
            if ($recActive) {
                if ($recType === 'weekly') {
                    $next = date('Y-m-d', strtotime($date.' +7 days'));
                } elseif ($recType === 'monthly') {
                    if ($alignToPeriod) {
                        $ref = new \DateTime($date);
                        $ten = (clone $ref)->setDate((int)$ref->format('Y'), (int)$ref->format('n'), 10);
                        if ($ref > $ten) {
                            $firstNext = (clone $ref)->modify('first day of next month');
                            $ten = $firstNext->setDate((int)$firstNext->format('Y'), (int)$firstNext->format('n'), 10);
                        }
                        $next = $ten->format('Y-m-d');
                    } else {
                        $next = CostsRecurrence::addMonthSafe($date);
                    }
                } elseif ($recType === 'yearly') {
                    $next = date('Y-m-d', strtotime($date.' +1 year'));
                } elseif ($recType === 'installments') {
                    if ($alignToPeriod) {
                        $ref = new \DateTime($date);
                        $ten = (clone $ref)->setDate((int)$ref->format('Y'), (int)$ref->format('n'), 10);
                        if ($ref > $ten) {
                            $firstNext = (clone $ref)->modify('first day of next month');
                            $ten = $firstNext->setDate((int)$firstNext->format('Y'), (int)$firstNext->format('n'), 10);
                        }
                        $next = $ten->format('Y-m-d');
                    } else {
                        $next = CostsRecurrence::addMonthSafe($date);
                    }
                }
            }
            (new Cost())->updateRecurrence($id, [
                'recorrente_tipo' => $recType,
                'recorrente_ativo' => $recActive,
                'recorrente_proxima_data' => $recActive ? $next : null,
                'parcelas_total' => $recType === 'installments' ? ($parcelasTotal ?: 1) : null,
                'parcela_atual' => $recType === 'installments' ? 1 : null,
            ]);
        }
        (new Log())->add(Auth::user()['id'] ?? null, 'custos', 'update', $id, json_encode(['data'=>$date,'categoria'=>$cat]));
        $this->redirect('/admin/costs');
    }

    public function exportCsv()
    {
        $this->requireRole(['admin']);
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
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $runner = new CostsRecurrence();
        $count = $runner->runDue(date('Y-m-d'));
        (new Log())->add(Auth::user()['id'] ?? null, 'custos', 'recurrence_run', null, json_encode(['generated'=>$count]));
        $this->redirect('/admin/costs');
    }
}

```php
<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Report;
use Models\Attendance;
use Models\User;
use Models\MonthlySnapshot;
use Models\Setting;

class PerformanceController extends Controller
{
    public function index()
    {
        $u = Auth::user();
        if (!$u) { http_response_code(401); echo 'Não autenticado'; return; }
        $role = (string)($u['role'] ?? 'seller');
        if (!in_array($role, ['admin','seller','manager','trainee'], true)) {
            http_response_code(403); $this->render('errors/403', ['title'=>'Acesso negado']); return;
        }

        $from = (string)($_GET['from'] ?? '');
        $to = (string)($_GET['to'] ?? '');
        if (!$from || !$to) {
            try { [$from, $to] = (new Setting())->currentPeriod(); } catch (\Throwable $e) { $from = date('Y-m-01'); $to = date('Y-m-t'); }
        }
        $fromTs = $from . ' 00:00:00';
        $toTs = $to . ' 23:59:59';

        $itemsByUser = [];
        $snapModel = new MonthlySnapshot();
        $companySnap = $snapModel->loadCompanyForPeriod($from, $to);
        if ($companySnap) {
            $snapSellers = $snapModel->loadSellersForPeriod($from, $to);
            foreach ($snapSellers as $row) {
                $uid = (int)($row['seller_id'] ?? 0);
                if ($uid <= 0) { continue; }
                $usdRate = (float)($row['usd_rate'] ?? 0);
                if ($usdRate <= 0) { $usdRate = 5.83; }
                $bruto = (float)($row['bruto_total_usd'] ?? 0);
                $liq = (float)($row['liquido_total_usd'] ?? 0);
                $liqAp = (float)($row['liquido_apurado_usd'] ?? 0);
                $commUsd = (float)($row['comissao_usd'] ?? 0);
                $itemsByUser[$uid] = [
                    'vendedor_id' => $uid,
                    'bruto_total' => $bruto,
                    'liquido_total' => $liq,
                    'liquido_apurado' => $liqAp,
                    'comissao_final' => $commUsd,
                    'bruto_total_brl' => $bruto * $usdRate,
                    'liquido_total_brl' => $liq * $usdRate,
                    'liquido_apurado_brl' => $liqAp * $usdRate,
                    'comissao_final_brl' => ((float)($row['comissao_brl'] ?? 0)) ?: ($commUsd * $usdRate),
                    'user' => [
                        'role' => (string)($row['seller_role'] ?? 'seller'),
                    ],
                ];
            }
        } else {
            $comm = new Commission();
            $calc = $comm->computeRange($fromTs, $toTs);
            foreach (($calc['items'] ?? []) as $it) {
                $itemsByUser[(int)($it['vendedor_id'] ?? 0)] = $it;
            }
        }

        $report = new Report();
        $attModel = new Attendance();

        $isAdmin = ($role === 'admin');
        $users = [];
        if ($isAdmin) {
            $users = (new User())->listByRoles(['seller','manager','trainee']);
        } else {
            $users = [[
                'id' => (int)($u['id'] ?? 0),
                'name' => (string)($u['name'] ?? $u['email'] ?? 'Usuário'),
                'email' => (string)($u['email'] ?? ''),
            ]];
        }

        $dataByUser = [];
        foreach ($users as $usr) {
            $uid = (int)($usr['id'] ?? 0);
            if ($uid <= 0) continue;
            $it = $itemsByUser[$uid] ?? [
                'bruto_total' => 0.0,
                'liquido_total' => 0.0,
                'liquido_apurado' => 0.0,
                'comissao_final' => 0.0,
                'bruto_total_brl' => 0.0,
                'liquido_total_brl' => 0.0,
                'liquido_apurado_brl' => 0.0,
                'comissao_final_brl' => 0.0,
                'user' => ['role'=> 'seller'],
            ];
            $salesCount = $report->countInPeriodAll($from, $to, $uid);
            $attRows = $attModel->listRange($from, $to, $uid);
            $attTotal = 0; $attDone = 0;
            foreach ($attRows as $r) { $attTotal += (int)($r['total_atendimentos'] ?? 0); $attDone += (int)($r['total_concluidos'] ?? 0); }
            $topClients = $report->topClientsForSeller($uid, $from, $to, 5);

            $dataByUser[$uid] = [
                'user' => $usr,
                'role' => (string)($it['user']['role'] ?? 'seller'),
                'bruto_total_usd' => (float)($it['bruto_total'] ?? 0),
                'liquido_total_usd' => (float)($it['liquido_total'] ?? 0),
                'liquido_apurado_usd' => (float)($it['liquido_apurado'] ?? 0),
                'comissao_usd' => (float)($it['comissao_final'] ?? 0),
                'bruto_total_brl' => (float)($it['bruto_total_brl'] ?? 0),
                'liquido_total_brl' => (float)($it['liquido_total_brl'] ?? 0),
                'liquido_apurado_brl' => (float)($it['liquido_apurado_brl'] ?? 0),
                'comissao_brl' => (float)($it['comissao_final_brl'] ?? 0),
                'sales_count' => (int)$salesCount,
                'att_total' => (int)$attTotal,
                'att_done' => (int)$attDone,
                'top_clients' => $topClients,
            ];
        }

        $this->render('performance/index', [
            'title' => 'Desempenho Individual',
            'from' => $from,
            'to' => $to,
            'is_admin' => $isAdmin,
            'dataByUser' => $dataByUser,
        ]);
    }

    public function exportSellerPdf()
    {
        $u = Auth::user();
        if (!$u) { http_response_code(401); echo 'Não autenticado'; return; }
        $role = (string)($u['role'] ?? 'seller');
        if (!in_array($role, ['admin','seller','manager','trainee'], true)) { http_response_code(403); echo 'Acesso negado'; return; }

        $from = (string)($_GET['from'] ?? '');
        $to = (string)($_GET['to'] ?? '');
        if (!$from || !$to) {
            try { [$from, $to] = (new Setting())->currentPeriod(); } catch (\Throwable $e) { $from = date('Y-m-01'); $to = date('Y-m-t'); }
        }
        $targetId = (int)($_GET['usuario_id'] ?? 0);
        if ($role !== 'admin') { $targetId = (int)($u['id'] ?? 0); }
        if ($targetId <= 0) { http_response_code(400); echo 'Usuário inválido'; return; }

        $userModel = new User();
        $user = $userModel->findById($targetId);
        if (!$user) { http_response_code(404); echo 'Usuário não encontrado'; return; }

        $fromTs = $from.' 00:00:00';
        $toTs = $to.' 23:59:59';
        $comm = new \Models\Commission();
        $calc = $comm->computeRange($fromTs, $toTs);
        $it = null;
        foreach (($calc['items'] ?? []) as $row) { if ((int)($row['vendedor_id'] ?? 0) === $targetId) { $it = $row; break; } }
        if (!$it) {
            $it = [
                'bruto_total' => 0.0,
                'liquido_total' => 0.0,
                'liquido_apurado' => 0.0,
                'comissao_final' => 0.0,
                'bruto_total_brl' => 0.0,
                'liquido_total_brl' => 0.0,
                'liquido_apurado_brl' => 0.0,
                'comissao_final_brl' => 0.0,
                'user' => ['role'=> $user['role'] ?? 'seller'],
            ];
        }
        $report = new \Models\Report();
        $salesCount = $report->countInPeriodAll($from, $to, $targetId);
        $attRows = (new \Models\Attendance())->listRange($from, $to, $targetId);
        $attTotal = 0; $attDone = 0; foreach ($attRows as $r) { $attTotal += (int)($r['total_atendimentos'] ?? 0); $attDone += (int)($r['total_concluidos'] ?? 0); }
        $topClients = $report->topClientsForSeller($targetId, $from, $to, 5);

        ob_start();
        $title = 'Desempenho Individual';
        $d = [
            'user' => ['id'=>$targetId,'name'=>$user['name'] ?? $user['email'] ?? 'Usuário','email'=>$user['email'] ?? ''],
            'role' => (string)($it['user']['role'] ?? ($user['role'] ?? 'seller')),
            'bruto_total_usd' => (float)($it['bruto_total'] ?? 0),
            'liquido_total_usd' => (float)($it['liquido_total'] ?? 0),
            'liquido_apurado_usd' => (float)($it['liquido_apurado'] ?? 0),
            'comissao_usd' => (float)($it['comissao_final'] ?? 0),
            'bruto_total_brl' => (float)($it['bruto_total_brl'] ?? 0),
            'liquido_total_brl' => (float)($it['liquido_total_brl'] ?? 0),
            'liquido_apurado_brl' => (float)($it['liquido_apurado_brl'] ?? 0),
            'comissao_brl' => (float)($it['comissao_final_brl'] ?? 0),
            'sales_count' => (int)$salesCount,
            'att_total' => (int)$attTotal,
            'att_done' => (int)$attDone,
            'top_clients' => $topClients,
        ];
        $from_ = $from; $to_ = $to; $user_ = $user;
        include dirname(__DIR__) . '/views/performance/partials_pdf.php';
        $html = (string)ob_get_clean();

        $filename = preg_replace('/[^A-Za-z0-9 _.-]/', '', ($user['name'] ?? $user['email'] ?? 'usuario')) . ' - desempenho.pdf';
        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream($filename, ['Attachment' => true]);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="padding:16px;font-family:Arial,sans-serif">'
            .'<p><strong>Dompdf não encontrado.</strong> Instale com:</p>'
            .'<pre>composer require dompdf/dompdf</pre>'
            .'<hr>'.$html.'</div>';
    }
}

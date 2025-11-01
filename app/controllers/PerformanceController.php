<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Commission;
use Models\Report;
use Models\Attendance;
use Models\User;

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

        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-t'));
        $fromTs = $from . ' 00:00:00';
        $toTs = $to . ' 23:59:59';

        $comm = new Commission();
        $calc = $comm->computeRange($fromTs, $toTs);
        $itemsByUser = [];
        foreach (($calc['items'] ?? []) as $it) {
            $itemsByUser[(int)($it['vendedor_id'] ?? 0)] = $it;
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
}

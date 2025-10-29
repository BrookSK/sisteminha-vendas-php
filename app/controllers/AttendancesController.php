<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Attendance;
use Models\Log;

class AttendancesController extends Controller
{
    public function index()
    {
        $attendance = new Attendance();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;
        $u = Auth::user();
        $role = $u['role'] ?? 'seller';
        $uid = ($role === 'seller') ? ($u['id'] ?? null) : null;
        $items = $attendance->list($limit, $offset, $uid);
        $total = $attendance->count($uid);
        $today = date('Y-m-d');
        $this->render('attendances/index', [
            'title' => 'Atendimentos',
            'items' => $items,
            'today' => $today,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    public function save()
    {
        $this->csrfCheck();
        $u = Auth::user();
        $role = $u['role'] ?? 'seller';
        $date = $_POST['data'] ?: date('Y-m-d');
        $total = (int)($_POST['total_atendimentos'] ?? 0);
        $done = (int)($_POST['total_concluidos'] ?? 0);
        $targetUserId = (int)($u['id'] ?? 0);
        // Managers/Admins can edit for any user when usuario_id is provided
        if (in_array($role, ['manager','admin'], true)) {
            $postedUid = (int)($_POST['usuario_id'] ?? 0);
            if ($postedUid > 0) { $targetUserId = $postedUid; }
        }
        (new Attendance())->upsert($date, $total, $done, $targetUserId ?: null);
        (new Log())->add($u['id'] ?? null, 'atendimentos', 'upsert', null, json_encode(['data'=>$date,'total'=>$total,'done'=>$done,'usuario_id'=>$targetUserId]));
        $this->redirect('/admin/attendances');
    }

    public function exportCsv()
    {
        // Exporta todos os atendimentos (ou poderia receber período)
        $attendance = new Attendance();
        $items = $attendance->list(10000, 0);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="atendimentos.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['data','total_atendimentos','total_concluidos','usuario_email','created_at']);
        foreach ($items as $i) {
            fputcsv($out, [$i['data'],$i['total_atendimentos'],$i['total_concluidos'],$i['usuario_email'] ?? '',$i['created_at']]);
        }
        fclose($out);
        exit;
    }

    public function delete()
    {
        $this->csrfCheck();
        $u = \Core\Auth::user();
        $role = $u['role'] ?? 'seller';
        if ($role !== 'admin') { http_response_code(403); echo 'Acesso negado'; return; }
        $date = (string)($_POST['data'] ?? '');
        $uid = (int)($_POST['usuario_id'] ?? 0);
        if (!$date || $uid<=0) { http_response_code(400); echo 'Parâmetros inválidos'; return; }
        (new Attendance())->delete($date, $uid);
        (new Log())->add($u['id'] ?? null, 'atendimentos', 'delete', null, json_encode(['data'=>$date,'usuario_id'=>$uid]));
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Atendimento excluído com sucesso.'];
        $this->redirect('/admin/attendances');
    }
}

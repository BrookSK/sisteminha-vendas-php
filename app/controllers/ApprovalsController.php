<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Approval;
use Models\Client;
use Models\Notification;
use Models\InternationalSale;
use Models\NationalSale;
use Models\Purchase;
use Models\User;

class ApprovalsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $me = Auth::user();
        $rows = (new Approval())->listPendingForReviewer((int)($me['id'] ?? 0), 100, 0);
        // map creator ids to names for display
        $creatorIds = array_values(array_unique(array_map(function($r){ return (int)($r['created_by'] ?? 0); }, $rows)));
        $creatorsMap = [];
        if ($creatorIds) {
            $u = new User();
            foreach ($u->allBasic() as $ub) {
                $creatorsMap[(int)$ub['id']] = (string)($ub['name'] ?? $ub['email'] ?? ('#'.$ub['id']));
            }
        }
        $this->render('approvals/index', [
            'title' => 'Aprovações Pendentes',
            'items' => $rows,
            'creatorsMap' => $creatorsMap,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function approve()
    {
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/approvals');
        $me = Auth::user();
        $apprModel = new Approval();
        $appr = $apprModel->find($id);
        if (!$appr || ($appr['status'] ?? '') !== 'pending') return $this->redirect('/admin/approvals');
        // Authorization: admin/manager OR assigned reviewer
        $role = (string)($me['role'] ?? 'seller');
        $isReviewer = ((int)($appr['reviewer_id'] ?? 0) === (int)($me['id'] ?? 0));
        if (!in_array($role, ['admin','manager'], true) && !$isReviewer) {
            http_response_code(403);
            return $this->render('errors/403', [ 'title' => 'Acesso negado', 'required_roles' => ['admin','manager','assigned reviewer'], 'user' => $me ]);
        }

        // Materialize based on entity_type
        $etype = (string)($appr['entity_type'] ?? '');
        $payload = json_decode((string)($appr['payload'] ?? '[]'), true) ?: [];
        $createdBy = (int)($appr['created_by'] ?? 0);
        if ($etype === 'client') {
            $cli = new Client();
            if (($appr['action'] ?? '') === 'create') {
                $cid = $cli->create($payload);
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Cliente aprovado', 'Seu cadastro de cliente foi aprovado.', 'approval', 'approved', [$createdBy]);
            } elseif (($appr['action'] ?? '') === 'update') {
                $cid = (int)($payload['id'] ?? 0);
                $data = (array)($payload['data'] ?? []);
                if ($cid > 0) { $cli->update($cid, $data); }
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Edição de cliente aprovada', 'Sua edição de cliente foi aprovada.', 'approval', 'approved', [$createdBy]);
            }
        } elseif ($etype === 'intl_sale') {
            $sale = new InternationalSale();
            if (($appr['action'] ?? '') === 'create') {
                $sid = $sale->create($payload, $createdBy, (string)($me['name'] ?? $me['email'] ?? ''));
                (new Purchase())->upsertFromIntl($sid);
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Venda Internacional aprovada', 'Sua venda internacional foi aprovada.', 'approval', 'approved', [$createdBy]);
            } elseif (($appr['action'] ?? '') === 'update') {
                $sid = (int)($payload['id'] ?? 0);
                $data = (array)($payload['data'] ?? []);
                if ($sid > 0) {
                    $sale->update($sid, $data, (string)($me['name'] ?? $me['email'] ?? ''), true);
                    (new Purchase())->upsertFromIntl($sid);
                }
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Edição de Venda Internacional aprovada', 'Sua edição de venda internacional foi aprovada.', 'approval', 'approved', [$createdBy]);
            } elseif (($appr['action'] ?? '') === 'delete') {
                $sid = (int)($payload['id'] ?? 0);
                if ($sid > 0) { $sale->delete($sid); }
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Exclusão de Venda Internacional aprovada', 'Sua solicitação de exclusão foi aprovada e a venda foi removida.', 'approval', 'approved', [$createdBy]);
            }
        } elseif ($etype === 'nat_sale') {
            $sale = new NationalSale();
            if (($appr['action'] ?? '') === 'create') {
                $sid = $sale->create($payload, $createdBy);
                (new Purchase())->upsertFromNat($sid);
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Venda Nacional aprovada', 'Sua venda nacional foi aprovada.', 'approval', 'approved', [$createdBy]);
            } elseif (($appr['action'] ?? '') === 'update') {
                $sid = (int)($payload['id'] ?? 0);
                $data = (array)($payload['data'] ?? []);
                if ($sid > 0) {
                    $sale->update($sid, $data, (string)($me['name'] ?? $me['email'] ?? ''), true);
                    (new Purchase())->upsertFromNat($sid);
                }
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Edição de Venda Nacional aprovada', 'Sua edição de venda nacional foi aprovada.', 'approval', 'approved', [$createdBy]);
            } elseif (($appr['action'] ?? '') === 'delete') {
                $sid = (int)($payload['id'] ?? 0);
                if ($sid > 0) { $sale->delete($sid); }
                $apprModel->approve($id, (int)($me['id'] ?? 0));
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Exclusão de Venda Nacional aprovada', 'Sua solicitação de exclusão foi aprovada e a venda foi removida.', 'approval', 'approved', [$createdBy]);
            }
        } else {
            $apprModel->approve($id, (int)($me['id'] ?? 0));
        }
        // Archive related notification for the approver (by [approval-id:<id>] token)
        try { (new Notification())->archiveByApprovalIdForUser((int)($me['id'] ?? 0), (int)$id); } catch (\Throwable $e) {}
        $this->flash('success', 'Aprovação realizada.');
        // Sellers-reviewers may not have access to /admin/approvals
        if (!in_array($role, ['admin','manager'], true)) {
            return $this->redirect('/admin/notifications');
        }
        return $this->redirect('/admin/approvals');
    }

    public function reject()
    {
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/approvals');
        $me = Auth::user();
        $apprModel = new Approval();
        $appr = $apprModel->find($id);
        if (!$appr) return $this->redirect('/admin/approvals');
        // Authorization: admin/manager OR assigned reviewer
        $role = (string)($me['role'] ?? 'seller');
        $isReviewer = ((int)($appr['reviewer_id'] ?? 0) === (int)($me['id'] ?? 0));
        if (!in_array($role, ['admin','manager'], true) && !$isReviewer) {
            http_response_code(403);
            return $this->render('errors/403', [ 'title' => 'Acesso negado', 'required_roles' => ['admin','manager','assigned reviewer'], 'user' => $me ]);
        }
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            $this->flash('danger','Informe um motivo para a rejeição.');
            // Sellers-reviewers may not have access to /admin/approvals
            if (!in_array($role, ['admin','manager'], true)) { return $this->redirect('/admin/notifications'); }
            return $this->redirect('/admin/approvals');
        }
        $apprModel->reject($id, (int)($me['id'] ?? 0));
        $createdBy = (int)($appr['created_by'] ?? 0);
        if ($createdBy) {
            $msg = 'Sua solicitação foi rejeitada pelo supervisor.' . "\nMotivo: " . $reason;
            (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Solicitação rejeitada', $msg, 'approval', 'rejected', [$createdBy]);
        }
        // Archive related notification for the approver (by [approval-id:<id>] token)
        try { (new Notification())->archiveByApprovalIdForUser((int)($me['id'] ?? 0), (int)$id); } catch (\Throwable $e) {}
        $this->flash('success','Solicitação rejeitada.');
        // Sellers-reviewers may not have access to /admin/approvals
        if (!in_array($role, ['admin','manager'], true)) {
            return $this->redirect('/admin/notifications');
        }
        return $this->redirect('/admin/approvals');
    }
}

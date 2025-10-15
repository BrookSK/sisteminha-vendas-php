<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Notification;
use Models\User; // if exists; otherwise we will query directly
use PDO;

class NotificationsController extends Controller
{
    private function ensureLogged(): void { if (!Auth::check()) $this->redirect('/login'); }
    private function ensureAdminOrManager(): void { $this->requireRole(['admin','manager']); }

    public function index()
    {
        $this->ensureLogged();
        $me = Auth::user();
        $model = new Notification();
        $filters = [
            'type' => trim($_GET['type'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'from' => trim($_GET['from'] ?? ''),
            'to' => trim($_GET['to'] ?? ''),
            'created_by' => isset($_GET['created_by']) && $_GET['created_by'] !== '' ? (int)$_GET['created_by'] : null,
            'arch' => $_GET['arch'] ?? '0',
        ];
        $list = $model->listForUserFiltered((int)($me['id'] ?? 0), $filters, 50, 0);
        $this->render('notifications/index', [
            'title' => 'Notificações',
            'items' => $list,
            'filters' => $filters,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function new()
    {
        $this->ensureAdminOrManager();
        $this->render('notifications/form', [
            'title' => 'Nova Notificação',
            'action' => '/admin/notifications/create',
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->ensureAdminOrManager();
        $this->csrfCheck();
        $me = Auth::user();
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'Informação');
        $status = trim($_POST['status'] ?? 'ativa');
        // Auto-target by creator role
        $role = (string)($me['role'] ?? 'seller');
        $targets = [];
        if ($role === 'admin') { $targets = ['manager','seller']; }
        elseif ($role === 'manager') { $targets = ['admin','seller']; }
        else { $targets = ['admin','manager']; }
        // If manual targets provided, merge
        if (!empty($_POST['targets']) && is_array($_POST['targets'])) {
            $targets = array_values(array_unique(array_merge($targets, array_map('strval', $_POST['targets']))));
        }
        $model = new Notification();
        $model->createWithTargets((int)($me['id'] ?? 0), $title, $message, $type, $status, (array)$targets);
        return $this->redirect('/admin/notifications');
    }

    public function markRead()
    {
        $this->ensureLogged();
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        (new Notification())->markReadFor((int)(Auth::user()['id'] ?? 0), $id);
        return $this->redirect('/admin/notifications');
    }

    public function markUnread()
    {
        $this->ensureLogged();
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        (new Notification())->markUnreadFor((int)(Auth::user()['id'] ?? 0), $id);
        return $this->redirect('/admin/notifications');
    }

    public function archive()
    {
        $this->ensureLogged();
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        (new Notification())->archiveFor((int)(Auth::user()['id'] ?? 0), $id);
        return $this->redirect('/admin/notifications');
    }

    public function delete()
    {
        $this->ensureAdminOrManager();
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Notification())->delete($id);
        }
        return $this->redirect('/admin/notifications');
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Demand;
use Models\Project;
use Models\TimeOff;
use Models\User;
use Models\Notification;

class DemandsController extends Controller
{
    public function dashboard()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $user = Auth::user();
        $role = $user['role'] ?? 'seller';
        $restrict = ($role !== 'admin');
        $uid = $user['id'] ?? null;

        $demand = new Demand();
        $backlog = $demand->backlog();
        $today = $demand->dueToday();
        $late = $demand->late();
        $week = $demand->weekSchedule();
        $off = (new TimeOff())->today();
        $users = (new User())->allBasic();
        // keep only active users
        $users = array_values(array_filter($users, function($u){ return (int)($u['ativo'] ?? 0) === 1; }));
        // notices (avisos gerais): show recent active non-archived
        $notices = [];
        try {
            $notices = (new Notification())->listForUserFiltered((int)($uid ?? 0), ['arch'=>'0'], 5, 0);
        } catch (\Throwable $e) {}

        $this->render('demands/dashboard', [
            'title' => 'Dashboard de Demandas',
            'backlog' => $backlog,
            'today' => $today,
            'late' => $late,
            'week' => $week,
            'time_off' => $off,
            'users' => $users,
            'notices' => $notices,
            'user' => $user,
        ]);
    }

    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $tab = $_GET['tab'] ?? 'pendentes';
        $user = Auth::user();
        $role = $user['role'] ?? 'seller';
        $uid = $user['id'] ?? null;
        $restrict = ($role !== 'admin');

        $items = (new Demand())->listAll($tab, $uid, $restrict);
        $projects = (new Project())->list(500,0);
        $users = (new User())->allBasic();
        $users = array_values(array_filter($users, function($u){ return (int)($u['ativo'] ?? 0) === 1; }));
        $adminId = 0; foreach ($users as $u) { if (($u['role'] ?? '') === 'admin') { $adminId = (int)$u['id']; break; } }
        $this->render('demands/index', [
            'title' => 'Demandas',
            'tab' => $tab,
            'items' => $items,
            'projects' => $projects,
            'users' => $users,
            'adminId' => $adminId,
        ]);
    }

    public function create()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $this->csrfCheck();
        $user = Auth::user();
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'type_desc' => trim($_POST['type_desc'] ?? ''),
            'assignee_id' => isset($_POST['assignee_id']) && $_POST['assignee_id']!=='' ? (int)$_POST['assignee_id'] : null,
            'project_id' => isset($_POST['project_id']) && $_POST['project_id']!=='' ? (int)$_POST['project_id'] : null,
            'status' => $_POST['status'] ?? 'pendente',
            'due_date' => $_POST['due_date'] ?? null,
            'priority' => $_POST['priority'] ?? 'baixa',
            'classification' => $_POST['classification'] ?? null,
            'details' => $_POST['details'] ?? null,
        ];
        if ($data['title'] === '' || $data['type_desc'] === '' || empty($data['due_date'])) {
            return $this->redirect('/admin/demands');
        }
        // default assignee: admin if not provided
        if (empty($data['assignee_id'])) {
            try {
                $uList = (new User())->allBasic();
                foreach ($uList as $u) { if ((int)($u['ativo'] ?? 0) === 1 && ($u['role'] ?? '') === 'admin') { $data['assignee_id'] = (int)$u['id']; break; } }
            } catch (\Throwable $e) {}
        }
        $creatorId = (int)($user['id'] ?? 0);
        $newId = (new Demand())->create($data, $creatorId);
        // Notify creator and assignee (if any)
        try {
            $assigneeId = isset($data['assignee_id']) ? (int)$data['assignee_id'] : 0;
            $title = 'Nova demanda criada: ' . $data['title'];
            $msg = 'Demanda criada por ' . ($user['name'] ?? 'Usuário') . '. Prazo: ' . ($data['due_date'] ?? '-') . '.';
            $targetUsers = array_values(array_unique(array_filter([$creatorId, $assigneeId])));
            if (!empty($targetUsers)) {
                (new Notification())->createWithUsers($creatorId, $title, $msg, 'demand', 'info', $targetUsers);
            }
        } catch (\Throwable $e) { /* ignore notif errors */ }
        $this->redirect('/admin/demands');
    }

    public function assign()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        $assignee = isset($_POST['assignee_id']) && $_POST['assignee_id']!=='' ? (int)$_POST['assignee_id'] : null;
        if ($id > 0) {
            $d = new Demand();
            $row = $d->find($id);
            if ($row) {
                $d->updateRow($id, [
                    'title' => $row['title'],
                    'type_desc' => $row['type_desc'],
                    'assignee_id' => $assignee,
                    'project_id' => $row['project_id'],
                    'status' => $row['status'],
                    'due_date' => $row['due_date'],
                    'priority' => $row['priority'],
                    'classification' => $row['classification'],
                    'details' => $row['details'],
                ]);
                // Notify new assignee and creator
                try {
                    $creatorId = (int)($row['created_by'] ?? 0);
                    $assigneeId = $assignee ? (int)$assignee : 0;
                    $title = 'Demanda atribuída: ' . ($row['title'] ?? ('#'.$id));
                    $msg = 'Demanda atribuída por ' . ((Auth::user()['name'] ?? 'Usuário')) . '.';
                    $targetUsers = array_values(array_unique(array_filter([$creatorId, $assigneeId])));
                    if (!empty($targetUsers)) {
                        (new Notification())->createWithUsers((int)(Auth::user()['id'] ?? 0), $title, $msg, 'demand', 'info', $targetUsers);
                    }
                } catch (\Throwable $e) { /* ignore notif errors */ }
            }
        }
        return $this->redirect('/admin/demands/dashboard');
    }

    public function status()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $this->csrfCheck();
        $user = Auth::user();
        $uid = (int)($user['id'] ?? 0);
        $role = $user['role'] ?? 'seller';
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        if ($id <= 0 || $newStatus === '') return $this->redirect('/admin/demands');
        $d = new Demand();
        $row = $d->find($id);
        if (!$row) return $this->redirect('/admin/demands');
        // Permission: admin any; others only if created_by = uid
        if ($role !== 'admin' && (int)($row['created_by'] ?? -1) !== $uid) {
            return $this->redirect('/admin/demands');
        }
        // Apply status change (preserve other fields)
        $d->updateRow($id, [
            'title' => $row['title'],
            'type_desc' => $row['type_desc'],
            'assignee_id' => $row['assignee_id'],
            'project_id' => $row['project_id'],
            'status' => $newStatus,
            'due_date' => $row['due_date'],
            'priority' => $row['priority'],
            'classification' => $row['classification'],
            'details' => $row['details'],
        ]);
        // Notify assignee and creator
        try {
            $creatorId = (int)($row['created_by'] ?? 0);
            $assigneeId = (int)($row['assignee_id'] ?? 0);
            $title = 'Status da demanda alterado: ' . ($row['title'] ?? ('#'.$id));
            $msg = 'Novo status: ' . $newStatus . ' por ' . ($user['name'] ?? 'Usuário') . '.';
            $targets = array_values(array_unique(array_filter([$creatorId, $assigneeId])));
            if (!empty($targets)) {
                (new Notification())->createWithUsers($uid, $title, $msg, 'demand', 'info', $targets);
            }
        } catch (\Throwable $e) { /* ignore notif errors */ }
        return $this->redirect('/admin/demands');
    }

    public function update()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $this->csrfCheck();
        $user = Auth::user();
        $uid = (int)($user['id'] ?? 0);
        $role = $user['role'] ?? 'seller';
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/demands');
        $d = new Demand();
        $row = $d->find($id);
        if (!$row) return $this->redirect('/admin/demands');
        if ($role !== 'admin' && (int)($row['created_by'] ?? -1) !== $uid) {
            return $this->redirect('/admin/demands');
        }
        $data = [
            'title' => trim($_POST['title'] ?? $row['title']),
            'type_desc' => trim($_POST['type_desc'] ?? $row['type_desc']),
            'assignee_id' => isset($_POST['assignee_id']) && $_POST['assignee_id']!=='' ? (int)$_POST['assignee_id'] : $row['assignee_id'],
            'project_id' => isset($_POST['project_id']) && $_POST['project_id']!=='' ? (int)$_POST['project_id'] : $row['project_id'],
            'status' => $_POST['status'] ?? $row['status'],
            'due_date' => $_POST['due_date'] ?? $row['due_date'],
            'priority' => $_POST['priority'] ?? $row['priority'],
            'classification' => $_POST['classification'] ?? $row['classification'],
            'details' => $_POST['details'] ?? $row['details'],
        ];
        $d->updateRow($id, $data);
        // optional: notify assignee if changed
        try {
            $oldAssignee = (int)($row['assignee_id'] ?? 0);
            $newAssignee = (int)($data['assignee_id'] ?? 0);
            if ($newAssignee && $newAssignee !== $oldAssignee) {
                (new Notification())->createWithUsers($uid, 'Responsável alterado: '.$data['title'], 'Responsável atualizado por '.($user['name'] ?? 'Usuário').'.', 'demand', 'info', array_unique([$newAssignee, (int)($row['created_by'] ?? 0)]));
            }
        } catch (\Throwable $e) {}
        return $this->redirect('/admin/demands');
    }
}

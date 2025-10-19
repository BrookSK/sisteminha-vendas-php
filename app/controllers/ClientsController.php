<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Client;
use Models\Log;
use Models\Approval;
use Models\Notification;
use Models\User;

class ClientsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','organic','trainee','manager','admin']);
        $q = trim($_GET['q'] ?? '');
        $client = new Client();
        // Pagination: 20 per page
        $perPage = 20;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        // Sellers can now see all clients (no owner filter)
        $clients = $client->search($q ?: null, $perPage, $offset, null);
        $total = (int)$client->countAll($q ?: null);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $this->render('clients/index', [
            'title' => 'Clientes',
            'q' => $q,
            'clients' => $clients,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    // GET /admin/clients/search?q=... -> JSON for autocomplete
    public function search()
    {
        $this->requireRole(['seller','organic','trainee','manager','admin']);
        $q = trim($_GET['q'] ?? '');
        // Sellers can now search all clients (no owner filter)
        $rows = (new Client())->searchLite($q ?: null, 20, 0, null);
        $out = [];
        foreach ($rows as $c) {
            $label = trim(($c['nome'] ?? '').' '.($c['email'] ? '<'.$c['email'].'>' : '')); 
            if (!empty($c['suite'])) { $label .= ' ('.strtoupper($c['suite']).')'; }
            $out[] = [
                'id' => (int)$c['id'],
                'text' => $label,
                'suite' => $c['suite'] ?? null,
                'suite_br' => $c['suite_br'] ?? null,
                'suite_us' => $c['suite_us'] ?? null,
                'suite_red' => $c['suite_red'] ?? null,
                'suite_globe' => $c['suite_globe'] ?? null,
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }

    // GET /admin/clients/options?q=... -> JSON simple [{id,text}] for selects/autocomplete
    public function options()
    {
        $this->requireRole(['seller','organic','trainee','manager','admin']);
        $q = trim($_GET['q'] ?? '');
        $rows = (new Client())->searchLite($q ?: null, 20, 0, null);
        $out = [];
        foreach ($rows as $c) {
            $label = trim(($c['nome'] ?? ''));
            if (!empty($c['email'])) { $label .= ' <'.$c['email'].'>'; }
            if (!empty($c['suite'])) { $label .= ' ('.strtoupper($c['suite']).')'; }
            $out[] = ['id'=>(int)$c['id'], 'text'=>$label];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }

    public function new()
    {
        $this->render('clients/form', [
            'title' => 'Novo Cliente',
            'client' => null,
            'action' => '/admin/clients/create',
        ]);
    }

    public function create()
    {
        $this->csrfCheck();
        $data = $this->sanitize($_POST);
        $this->validate($data, true);
        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');
        if ($role === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $apprId = (new Approval())->createPending('client', 'create', $data, (int)($me['id'] ?? 0), $supervisorId, null);
            if ($supervisorId) {
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Aprovação de Cliente', 'Um novo cliente foi enviado por um trainee e aguarda sua aprovação. [approval-id:'.$apprId.']', 'approval', 'new', [$supervisorId]);
            }
            $this->flash('info', 'Cliente enviado para aprovação. Uma notificação foi enviada ao seu supervisor e está pendente de aprovação.');
            return $this->redirect('/admin/clients');
        }
        $model = new Client();
        $data['created_by'] = $me['id'] ?? null;
        $id = $model->create($data);
        (new Log())->add($me['id'] ?? null, 'cliente', 'create', $id, json_encode($data));
        return $this->redirect('/admin/clients');
    }

    // POST /admin/clients/create-ajax
    public function createAjax()
    {
        $this->csrfCheck();
        $this->requireRole(['seller','organic','trainee','manager','admin']);
        $in = $_POST;
        if (empty($in) && ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json') {
            $raw = file_get_contents('php://input');
            $in = json_decode($raw, true) ?: [];
        }
        $data = $this->sanitize($in);
        // Normalize suite from prefixed input (BR-/US-/RED-/GLOB-123) to legacy + per-site fields
        if (!empty($data['suite'])) {
            if (preg_match('/^(BR|US|RED|GLOB)-(\d+)$/i', $data['suite'], $m)) {
                $prefix = strtoupper($m[1]);
                $num = $m[2];
                // legacy suite uses letters+digits without dash
                $data['suite'] = ($prefix === 'GLOB' ? 'GLOB' : $prefix) . $num;
                if ($prefix === 'BR' && empty($data['suite_br'])) $data['suite_br'] = $num;
                if ($prefix === 'US' && empty($data['suite_us'])) $data['suite_us'] = $num;
                if ($prefix === 'RED' && empty($data['suite_red'])) $data['suite_red'] = $num;
                if ($prefix === 'GLOB' && empty($data['suite_globe'])) $data['suite_globe'] = $num;
            }
        }
        $this->validate($data, true);
        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');
        if ($role === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $apprId = (new Approval())->createPending('client', 'create', $data, (int)($me['id'] ?? 0), $supervisorId, null);
            if ($supervisorId) {
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Aprovação de Cliente', 'Um novo cliente foi enviado por um trainee e aguarda sua aprovação. [approval-id:'.$apprId.']', 'approval', 'new', [$supervisorId]);
            }
            header('Content-Type: application/json');
            echo json_encode(['pending' => true, 'message' => 'Cliente enviado para aprovação. Uma notificação foi enviada ao seu supervisor e está pendente de aprovação.']);
            return exit;
        }
        $data['created_by'] = $me['id'] ?? null;
        $model = new Client();
        $id = $model->create($data);
        (new Log())->add($me['id'] ?? null, 'cliente', 'create', $id, json_encode($data));
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $id,
            'nome' => $data['nome'],
            'email' => $data['email'],
            'suite' => $data['suite'] ?? null,
            'suite_br' => $data['suite_br'] ?? null,
            'suite_us' => $data['suite_us'] ?? null,
            'suite_red' => $data['suite_red'] ?? null,
            'suite_globe' => $data['suite_globe'] ?? null,
        ]);
        return exit;
    }

    public function edit()
    {
        $this->requireRole(['seller','organic','trainee','manager','admin']);
        $id = (int)($_GET['id'] ?? 0);
        $model = new Client();
        $client = $model->find($id);
        if (!$client) return $this->redirect('/admin/clients');
        $this->render('clients/form', [
            'title' => 'Editar Cliente',
            'client' => $client,
            'action' => '/admin/clients/update?id=' . $id,
        ]);
    }

    public function update()
    {
        $this->csrfCheck();
        $this->requireRole(['seller','trainee','manager','admin']);
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->sanitize($_POST);
        $this->validate($data, false);
        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');
        $model = new Client();
        $client = $model->find($id);
        if (!$client) return $this->redirect('/admin/clients');
        if ($role === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $apprId = (new Approval())->createPending('client', 'update', ['id'=>$id,'data'=>$data], (int)($me['id'] ?? 0), $supervisorId, $id);
            if ($supervisorId) {
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Aprovação de Cliente (edição)', 'Uma edição de cliente foi enviada por um trainee e aguarda sua aprovação. [approval-id:'.$apprId.']', 'approval', 'new', [$supervisorId]);
            }
            $this->flash('info', 'Edição enviada para aprovação. Uma notificação foi enviada ao seu supervisor e está pendente de aprovação.');
            return $this->redirect('/admin/clients');
        }
        $model->update($id, $data);
        (new Log())->add($me['id'] ?? null, 'cliente', 'update', $id, json_encode($data));
        return $this->redirect('/admin/clients');
    }

    public function delete()
    {
        $this->csrfCheck();
        $this->requireRole(['seller','manager','admin']);
        $id = (int)($_POST['id'] ?? 0);
        $model = new Client();
        $me = Auth::user();
        $client = $model->find($id);
        if ($id <= 0 || !$client) {
            return $this->redirect('/admin/clients');
        }
        if (($me['role'] ?? 'seller') === 'seller' && !$model->isOwner((int)($me['id'] ?? 0), $client)) {
            return $this->redirect('/admin/clients');
        }

        $counts = $model->hasRelatedSales($id);
        $totalRelated = (int)($counts['vendas'] ?? 0) + (int)($counts['vendas_internacionais'] ?? 0) + (int)($counts['vendas_nacionais'] ?? 0);

        // If there are related sales and not confirmed, show confirmation page
        if ($totalRelated > 0 && (($_POST['force'] ?? '0') !== '1')) {
            return $this->render('clients/confirm_delete', [
                'title' => 'Confirmar exclusão',
                'client' => $client,
                'counts' => $counts,
                'id' => $id,
            ]);
        }

        // Force deletion: detach and delete
        if ($totalRelated > 0) {
            $model->deleteForce($id);
        } else {
            $model->delete($id);
        }
        (new Log())->add($me['id'] ?? null, 'cliente', 'delete', $id, null);
        return $this->redirect('/admin/clients');
    }

    private function sanitize(array $in): array
    {
        $suiteLegacy = strtoupper(trim($in['suite'] ?? ''));
        $sbr = preg_replace('/\D+/', '', (string)($in['suite_br'] ?? ''));
        $sus = preg_replace('/\D+/', '', (string)($in['suite_us'] ?? ''));
        $sred = preg_replace('/\D+/', '', (string)($in['suite_red'] ?? ''));
        $sglb = preg_replace('/\D+/', '', (string)($in['suite_globe'] ?? ''));
        return [
            'nome' => trim($in['nome'] ?? ''),
            'email' => trim($in['email'] ?? ''),
            'telefone' => trim($in['telefone'] ?? ''),
            // legacy single suite (kept for backward compatibility); empty -> null
            'suite' => ($suiteLegacy === '') ? null : $suiteLegacy,
            // new per-site suites: only store numeric part; empty -> null
            'suite_br' => ($sbr === '') ? null : $sbr,
            'suite_us' => ($sus === '') ? null : $sus,
            'suite_red' => ($sred === '') ? null : $sred,
            'suite_globe' => ($sglb === '') ? null : $sglb,
            'endereco' => trim($in['endereco'] ?? ''),
            'observacoes' => trim($in['observacoes'] ?? ''),
        ];
    }

    private function validate(array $data, bool $creating): void
    {
        if ($data['nome'] === '') die('Nome é obrigatório');
        // Legacy suite pattern (optional) - allow up to 4 letters (e.g., GLOB123)
        if ($data['suite'] !== null && $data['suite'] !== '' && !preg_match('/^[A-Z]{1,3}\d+$/', (string)$data['suite'])) {
            die('Suite (legado) inválida. Use 1 a 3 letras seguidas de números (ex.: BR123, US1302, RED15202).');
        }
        // New suites: only digits allowed in each numeric part (ignore null/empty)
        foreach (['suite_br','suite_us','suite_red','suite_globe'] as $sf) {
            $val = (string)($data[$sf] ?? '');
            if ($val !== '' && !preg_match('/^\d+$/', $val)) {
                die('Campo de suite deve conter apenas números: ' . $sf);
            }
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            die('E-mail inválido');
        }

        // Uniqueness checks: each suite per prefix must be unique across all clients
        $model = new Client();
        $db = \Core\Database::pdo();
        // current id when updating
        $currentId = 0;
        if (!$creating) {
            $currentId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        }
        // Check legacy suite uniqueness if provided
        if (!empty($data['suite'])) {
            $stmt = $db->prepare('SELECT id FROM clientes WHERE suite = :s LIMIT 1');
            $stmt->execute([':s' => $data['suite']]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && (int)$row['id'] !== $currentId) {
                die('Suite já utilizada por outro cliente: ' . htmlspecialchars($data['suite']));
            }
        }
        // Check per-prefix numeric uniqueness
        $map = [
            'suite_br' => 'BR',
            'suite_us' => 'US',
            'suite_red' => 'RED',
            'suite_globe' => 'GLOB',
        ];
        foreach ($map as $col => $prefix) {
            $num = trim((string)($data[$col] ?? ''));
            if ($num === '') continue;
            $sql = 'SELECT id FROM clientes WHERE ' . $col . ' = :n LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':n' => $num]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && (int)$row['id'] !== $currentId) {
                die('Suite já utilizada por outro cliente: ' . $prefix . '-' . htmlspecialchars($num));
            }
        }
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Models\Log;
use Models\User;

class LogsController extends Controller
{
    public function index()
    {
        $q = [
            'entidade' => trim($_GET['entidade'] ?? ''),
            'acao' => trim($_GET['acao'] ?? ''),
            'usuario_id' => (int)($_GET['usuario_id'] ?? 0),
            'de' => trim($_GET['de'] ?? ''),
            'ate' => trim($_GET['ate'] ?? ''),
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $log = new Log();
        $items = $log->search($q, $limit, $offset);
        $total = $log->count($q);
        $users = (new User())->allBasic();

        $this->render('logs/index', [
            'title' => 'Logs de Atividade',
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'filters' => $q,
            'users' => $users,
        ]);
    }
}

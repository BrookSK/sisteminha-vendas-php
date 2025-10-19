<?php
namespace Core;

class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function render(string $template, array $data = [], string $layout = 'layouts/main')
    {
        echo $this->view->render($template, $data, $layout);
    }

    protected function redirect(string $path)
    {
        header('Location: ' . $path);
        exit;
    }

    protected function csrfCheck(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!Auth::checkCsrf($token)) {
            http_response_code(400);
            exit('CSRF token inválido');
        }
    }

    protected function requireRole(array $roles): void
    {
        $u = Auth::user();
        if (!$u || !in_array(($u['role'] ?? 'seller'), $roles, true)) {
            http_response_code(403);
            exit('Acesso negado');
        }
    }

    protected function flash(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $_SESSION['flash'] = $_SESSION['flash'] ?? [];
        $_SESSION['flash'][] = ['type'=>$type, 'message'=>$message];
    }
}

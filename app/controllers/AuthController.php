<?php
namespace Controllers;

use Core\Controller;
use Models\User;
use Core\Auth;

class AuthController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return $this->redirect('/admin');
        }
        $this->render('auth/login', [
            'title' => 'Login',
        ]);
    }

    public function doLogin()
    {
        $this->csrfCheck();

        // Throttling: 5 tentativas por 10 minutos
        $now = time();
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
        // remove tentativas antigas (>10min)
        $_SESSION['login_attempts'] = array_values(array_filter($_SESSION['login_attempts'], function($ts) use ($now){ return ($now - $ts) < 600; }));
        if (count($_SESSION['login_attempts']) >= 5) {
            return $this->render('auth/login', [
                'title' => 'Login',
                'error' => 'Muitas tentativas. Tente novamente em alguns minutos.'
            ]);
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user) {
            // Se não existe nenhum usuário, criar admin inicial
            if ($userModel->count() === 0) {
                $userId = $userModel->create('Admin', $email ?: 'admin@example.com', $password ?: 'admin123');
                $user = $userModel->findById($userId);
            }
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            // sucesso: limpar tentativas e regenerar session id
            $_SESSION['login_attempts'] = [];
            session_regenerate_id(true);
            Auth::login($user);
            // redirect back to originally requested page if set
            $next = $_SESSION['next_url'] ?? null;
            if ($next) { unset($_SESSION['next_url']); return $this->redirect($next); }
            return $this->redirect('/admin');
        }

        // falha: registra tentativa
        $_SESSION['login_attempts'][] = $now;
        $this->render('auth/login', [
            'title' => 'Login',
            'error' => 'Credenciais inválidas',
        ]);
    }

    public function logout()
    {
        Auth::logout();
        $this->redirect('/login');
    }
}

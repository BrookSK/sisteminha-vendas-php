<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\User;

class AccountController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','organic','manager','admin']);
        $user = Auth::user();
        $supervisor = null;
        try {
            $uModel = new User();
            $meFull = $uModel->findById((int)($user['id'] ?? 0));
            if (($meFull['role'] ?? '') === 'trainee' && (int)($meFull['supervisor_user_id'] ?? 0) > 0) {
                $supervisor = $uModel->findById((int)$meFull['supervisor_user_id']);
            }
        } catch (\Throwable $e) {}
        $this->render('account/index', [
            'title' => 'Minha Conta',
            'user' => $user,
            'supervisor' => $supervisor,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function updateProfile()
    {
        $this->requireRole(['seller','trainee','organic','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        if ($name !== '' && $email !== '') {
            (new User())->updateProfile((int)$u['id'], $name, $email, ($whatsapp !== '' ? $whatsapp : null));
            // refresh session
            $fresh = (new User())->findById((int)$u['id']);
            Auth::loginAs($fresh);
        }
        return $this->redirect('/admin/account');
    }

    public function updatePassword()
    {
        $this->requireRole(['seller','trainee','organic','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== '' && $new === $confirm) {
            $userModel = new User();
            if ($userModel->verifyPassword((int)$u['id'], $current)) {
                $userModel->updatePassword((int)$u['id'], $new);
            }
        }
        return $this->redirect('/admin/account');
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\User;

class AccountController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $user = Auth::user();
        $this->render('account/index', [
            'title' => 'Minha Conta',
            'user' => $user,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function updateProfile()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name !== '' && $email !== '') {
            (new User())->updateProfile((int)$u['id'], $name, $email);
            // refresh session
            $fresh = (new User())->findById((int)$u['id']);
            Auth::loginAs($fresh);
        }
        return $this->redirect('/admin/account');
    }

    public function updatePassword()
    {
        $this->requireRole(['seller','manager','admin']);
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

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\AppSettings;

class DnsSettingsController extends Controller
{
    public function index()
    {
        $this->requireRole(['manager','admin']);
        $s = new AppSettings();
        $vals = $s->all(['cf_api_token','cf_account_email']);
        $this->render('settings/dns', [
            'title' => 'Configurações DNS / Cloudflare',
            'cf_api_token' => $vals['cf_api_token'] ?? '',
            'cf_account_email' => $vals['cf_account_email'] ?? '',
        ]);
    }

    public function save()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $token = trim($_POST['cf_api_token'] ?? '');
        $email = trim($_POST['cf_account_email'] ?? '');
        $s = new AppSettings();
        $s->set('cf_api_token', $token !== '' ? $token : null);
        $s->set('cf_account_email', $email !== '' ? $email : null);
        $this->flash('success', 'Configurações DNS salvas.');
        return $this->redirect('/admin/settings/dns');
    }
}

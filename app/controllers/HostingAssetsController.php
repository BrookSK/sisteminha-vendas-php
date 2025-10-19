<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Hosting;
use Models\HostingAsset;

class HostingAssetsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $assets = (new HostingAsset())->list(1000, 0, isset($_GET['hosting_id']) ? (int)$_GET['hosting_id'] : null);
        $hostings = (new Hosting())->list(1000, 0);
        $this->render('hosting_assets/index', [
            'title' => 'Ativos (Sites/Sistemas/E-mails)',
            'assets' => $assets,
            'hostings' => $hostings,
        ]);
    }

    public function create()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $d = [
            'title' => trim($_POST['title'] ?? ''),
            'url' => trim($_POST['url'] ?? ''),
            'hosting_id' => !empty($_POST['hosting_id']) ? (int)$_POST['hosting_id'] : null,
            'type' => $_POST['type'] ?? 'site',
            'server_ip' => trim($_POST['server_ip'] ?? ''),
            'real_ip' => trim($_POST['real_ip'] ?? ''),
            'pointing_ok' => isset($_POST['pointing_ok']) ? (int)$_POST['pointing_ok'] : null,
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
        ];
        if ($d['server_ip'] === '' && !empty($d['hosting_id'])) {
            $h = (new Hosting())->find((int)$d['hosting_id']);
            if ($h && !empty($h['server_ip'])) { $d['server_ip'] = $h['server_ip']; }
        }
        if ($d['server_ip'] !== '' && $d['real_ip'] !== '') {
            $d['pointing_ok'] = (trim($d['server_ip']) === trim($d['real_ip'])) ? 1 : 0;
        }
        if ($d['title'] === '') { $this->flash('warning', 'Informe o título.'); return $this->redirect('/admin/hosting-assets'); }
        (new HostingAsset())->create($d, $u['id'] ?? null);
        $this->flash('success', 'Ativo criado.');
        return $this->redirect('/admin/hosting-assets');
    }

    public function update()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/hosting-assets');
        $d = [
            'title' => trim($_POST['title'] ?? ''),
            'url' => trim($_POST['url'] ?? ''),
            'hosting_id' => !empty($_POST['hosting_id']) ? (int)$_POST['hosting_id'] : null,
            'type' => $_POST['type'] ?? 'site',
            'server_ip' => trim($_POST['server_ip'] ?? ''),
            'real_ip' => trim($_POST['real_ip'] ?? ''),
            'pointing_ok' => isset($_POST['pointing_ok']) ? (int)$_POST['pointing_ok'] : null,
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
        ];
        (new HostingAsset())->updateRow($id, $d, $u['id'] ?? null);
        $this->flash('success', 'Ativo atualizado.');
        return $this->redirect('/admin/hosting-assets');
    }

    public function delete()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { (new HostingAsset())->delete($id); $this->flash('success', 'Ativo excluído.'); }
        return $this->redirect('/admin/hosting-assets');
    }

    public function refreshDns()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $res = (new HostingAsset())->refreshDNS($id);
            if ($res) {
                $msg = 'DNS verificado. Real IP: '.($res['real_ip'] ?? 'n/d').'. Apontamento '.(((int)($res['pointing_ok'] ?? -1) === 1) ? 'OK' : 'INCORRETO');
                $this->flash(((int)($res['pointing_ok'] ?? -1) === 1) ? 'success' : 'warning', $msg);
            } else {
                $this->flash('danger', 'Falha ao verificar DNS.');
            }
        }
        return $this->redirect('/admin/hosting-assets');
    }

    public function refreshDnsAll()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $hid = !empty($_POST['hosting_id']) ? (int)$_POST['hosting_id'] : null;
        $assets = (new HostingAsset())->list(1000, 0, $hid);
        $ok = 0; $bad = 0; $nd = 0; $count = 0;
        foreach ($assets as $a) {
            $count++;
            $res = (new HostingAsset())->refreshDNS((int)$a['id']);
            if (!$res) { $nd++; continue; }
            $p = (int)($res['pointing_ok'] ?? -1);
            if ($p === 1) $ok++; else if ($p === 0) $bad++; else $nd++;
            if ($count >= 100) break; // safety limit per request
        }
        $this->flash('info', "Verificação em lote concluída: OK=$ok, INCORRETO=$bad, N/D=$nd (máx. 100 itens por execução)");
        return $this->redirect('/admin/hosting-assets'.($hid?('?hosting_id='.$hid):''));
    }
}

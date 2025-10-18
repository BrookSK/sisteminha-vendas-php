<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Documentation;
use Models\DocumentationArea;
use Models\DocumentationEmailPermission;
use Models\DocumentationComment;

class DocumentationsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $u = Auth::user();
        $role = $u['role'] ?? 'seller';
        $allowed = ['all'];
        if ($role === 'seller') { $allowed = ['all','seller']; }
        if ($role === 'manager') { $allowed = ['all','seller','manager']; }
        if ($role === 'admin') { $allowed = ['all','seller','manager','admin']; }
        $filters = [
            'status' => trim($_GET['status'] ?? ''),
            'area_id' => ($_GET['area_id'] ?? '') !== '' ? (int)$_GET['area_id'] : null,
            'project_id' => ($_GET['project_id'] ?? '') !== '' ? (int)$_GET['project_id'] : null,
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 25);
        if ($perPage < 5) $perPage = 5; if ($perPage > 200) $perPage = 200;
        $offset = ($page - 1) * $perPage;
        $m = new Documentation();
        $total = $m->countFilteredByVisibility($allowed, $filters);
        $docs = $m->listFilteredByVisibility($allowed, $filters, $perPage, $offset);
        $areas = (new DocumentationArea())->listAll();
        $this->render('documentations/index', [
            'title' => 'Documentações e Procedimentos',
            'docs' => $docs,
            'areas' => $areas,
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function new()
    {
        $this->requireRole(['seller','manager','admin']);
        $areas = (new DocumentationArea())->listAll();
        $this->render('documentations/form', [
            'title' => 'Nova Documentação',
            'areas' => $areas,
            'doc' => null,
        ]);
    }

    public function create()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $d = [
            'title' => trim($_POST['title'] ?? ''),
            'status' => $_POST['status'] ?? 'nao_iniciada',
            'project_id' => !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null,
            'area_id' => !empty($_POST['area_id']) ? (int)$_POST['area_id'] : null,
            'internal_visibility' => $_POST['internal_visibility'] ?? 'all',
            'content' => $_POST['content'] ?? null,
        ];
        if ($d['title'] === '') {
            return $this->redirect('/admin/documentations');
        }
        (new Documentation())->create($d, $u['id'] ?? null);
        $this->flash('success', 'Documentação criada com sucesso.');
        return $this->redirect('/admin/documentations');
    }

    public function view()
    {
        $this->requireRole(['seller','manager','admin']);
        $u = Auth::user();
        $role = $u['role'] ?? 'seller';
        $id = (int)($_GET['id'] ?? 0);
        $doc = (new Documentation())->find($id);
        if (!$doc) return $this->redirect('/admin/documentations');
        // check visibility
        $vis = $doc['internal_visibility'] ?? 'all';
        if ($role !== 'admin') {
            if ($role === 'manager' && !in_array($vis, ['all','seller','manager'], true)) return $this->redirect('/admin/documentations');
            if ($role === 'seller' && !in_array($vis, ['all','seller'], true)) return $this->redirect('/admin/documentations');
        }
        $emails = (new DocumentationEmailPermission())->listByDoc((int)$doc['id']);
        $comments = (new DocumentationComment())->listByDoc((int)$doc['id']);
        $this->render('documentations/view', [
            'title' => 'Documentação: '.$doc['title'],
            'doc' => $doc,
            'emails' => $emails,
            'comments' => $comments,
        ]);
    }

    public function edit()
    {
        $this->requireRole(['seller','manager','admin']);
        $u = Auth::user();
        $role = $u['role'] ?? 'seller';
        $id = (int)($_GET['id'] ?? 0);
        $doc = (new Documentation())->find($id);
        if (!$doc) return $this->redirect('/admin/documentations');
        // Only admin/manager can edit any; seller can edit if visibility allows and (optionally) if created_by is self (could be tightened later)
        if ($role === 'seller' && !in_array(($doc['internal_visibility'] ?? 'all'), ['all','seller'], true)) {
            return $this->redirect('/admin/documentations');
        }
        $areas = (new DocumentationArea())->listAll();
        $this->render('documentations/form', [
            'title' => 'Editar Documentação',
            'areas' => $areas,
            'doc' => $doc,
        ]);
    }

    public function update()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/documentations');
        $doc = (new Documentation())->find($id);
        if (!$doc) return $this->redirect('/admin/documentations');
        $d = [
            'title' => trim($_POST['title'] ?? ''),
            'status' => $_POST['status'] ?? 'nao_iniciada',
            'project_id' => !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null,
            'area_id' => !empty($_POST['area_id']) ? (int)$_POST['area_id'] : null,
            'internal_visibility' => $_POST['internal_visibility'] ?? 'all',
            'content' => $_POST['content'] ?? null,
        ];
        if ($d['title'] === '') {
            return $this->redirect('/admin/documentations');
        }
        (new Documentation())->updateRow($id, $d, $u['id'] ?? null);
        $this->flash('success', 'Documentação atualizada.');
        return $this->redirect('/admin/documentations/view?id='.(int)$id);
    }

    public function publicView()
    {
        // Public view by slug via query ?s=
        $slug = trim($_GET['s'] ?? '');
        if ($slug === '') { http_response_code(404); echo 'Not found'; return; }
        $doc = (new Documentation())->findBySlug($slug);
        if (!$doc || (int)($doc['published'] ?? 0) !== 1) { http_response_code(404); echo 'Not found'; return; }
        $email = trim($_GET['email'] ?? '');
        $title = $doc['title'];
        if ($email === '') {
            // ask for email via modal
            $content = '<div class="container my-4"><h2>'.htmlspecialchars($title).'</h2><div class="mt-3">'
                .'<div class="alert alert-info">Informe seu e-mail para acessar este documento.</div>'
                .'</div></div>'
                .'<div class="modal fade show" id="emailGateModal" tabindex="-1" style="display:block; background: rgba(0,0,0,.5);" aria-modal="true" role="dialog">'
                .'<div class="modal-dialog"><div class="modal-content">'
                .'<div class="modal-header"><h5 class="modal-title">Acesso por e-mail</h5></div>'
                .'<div class="modal-body">'
                .'<form method="get" action="/docs">'
                .'<input type="hidden" name="s" value="'.htmlspecialchars($slug).'">'
                .'<label class="form-label">Seu e-mail</label>'
                .'<input type="email" name="email" class="form-control" required placeholder="usuario@dominio.com">'
                .'</div>'
                .'<div class="modal-footer">'
                .'<button type="submit" class="btn btn-primary">Continuar</button>'
                .'</div></form>'
                .'</div></div></div>'
                .'<script>document.addEventListener("DOMContentLoaded",function(){ /* modal already shown */ });</script>';
            include dirname(__DIR__) . '/views/layouts/main.php';
            return;
        }
        if (!(new \Models\DocumentationEmailPermission())->isAllowed((int)$doc['id'], $email)) {
            // unauthorized: show modal with error
            $content = '<div class="container my-4"><h2>'.htmlspecialchars($title).'</h2><div class="mt-3">'
                .'<div class="alert alert-danger">Acesso negado para o e-mail informado.</div>'
                .'</div></div>'
                .'<div class="modal fade show" id="emailGateModal" tabindex="-1" style="display:block; background: rgba(0,0,0,.5);" aria-modal="true" role="dialog">'
                .'<div class="modal-dialog"><div class="modal-content">'
                .'<div class="modal-header"><h5 class="modal-title">Acesso por e-mail</h5></div>'
                .'<div class="modal-body">'
                .'<form method="get" action="/docs">'
                .'<input type="hidden" name="s" value="'.htmlspecialchars($slug).'">'
                .'<label class="form-label">Seu e-mail</label>'
                .'<input type="email" name="email" class="form-control" required placeholder="usuario@dominio.com">'
                .'</div>'
                .'<div class="modal-footer">'
                .'<button type="submit" class="btn btn-primary">Tentar novamente</button>'
                .'</div></form>'
                .'</div></div></div>'
                .'<script>document.addEventListener("DOMContentLoaded",function(){ /* modal already shown */ });</script>';
            include dirname(__DIR__) . '/views/layouts/main.php';
            return;
        }
        // authorized: render content
        $content = '<div class="container my-4"><h2>'.htmlspecialchars($title).'</h2><div class="mt-3">'.($doc['content'] ?? '').'</div></div>';
        include dirname(__DIR__) . '/views/layouts/main.php';
    }

    public function publish()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        $published = ($_POST['published'] ?? '0') === '1';
        if ($id > 0) {
            $m = new Documentation();
            $m->setPublished($id, $published);
            if ($published) {
                $doc = $m->find($id);
                $slug = (string)($doc['external_slug'] ?? '');
                if ($slug === '') {
                    $base = $m->makeSlug((string)($doc['title'] ?? ''));
                    $slug = $m->nextAvailableSlug($base, $id);
                    $m->setSlug($id, $slug);
                }
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $url = $scheme . '://' . $host . '/docs/?s=' . urlencode($slug);
                $this->flash('success', 'Documentação publicada. Link: ' . $url);
            } else {
                $this->flash('success', 'Documentação despublicada.');
            }
        }
        return $this->redirect('/admin/documentations/view?id='.(int)$id);
    }

    public function setSlug()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        $slug = trim($_POST['slug'] ?? '');
        if ($id <= 0) { $this->flash('warning','ID inválido.'); return $this->redirect('/admin/documentations'); }
        if ($slug === '') {
            (new Documentation())->setSlug($id, null);
            $this->flash('success', 'Slug removido.');
            return $this->redirect('/admin/documentations/view?id='.(int)$id);
        }
        // sanitize slug
        $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '-', $slug));
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');
        if ($slug === '' || (new Documentation())->slugExists($slug, $id)) {
            $this->flash('danger', 'Slug inválido ou já em uso.');
            return $this->redirect('/admin/documentations/view?id='.(int)$id);
        }
        (new Documentation())->setSlug($id, $slug);
        $this->flash('success', 'Slug definido.');
        return $this->redirect('/admin/documentations/view?id='.(int)$id);
    }

    public function emailAdd()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        if ($id > 0 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            (new DocumentationEmailPermission())->add($id, $email);
            $this->flash('success', 'E-mail autorizado adicionado.');
        } else {
            $this->flash('warning', 'Informe um e-mail válido.');
        }
        return $this->redirect('/admin/documentations/view?id='.(int)$id);
    }

    public function emailRemove()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        if ($id > 0 && $email !== '') {
            (new DocumentationEmailPermission())->remove($id, $email);
            $this->flash('success', 'E-mail autorizado removido.');
        }
        return $this->redirect('/admin/documentations/view?id='.(int)$id);
    }

    public function commentAdd()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $id = (int)($_POST['id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        if ($id > 0 && $content !== '') {
            (new DocumentationComment())->add($id, (int)($u['id'] ?? 0), $content);
        }
        return $this->redirect('/admin/documentations/view?id='.(int)$id);
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\DocumentationArea;

class DocumentationAreasController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $areas = (new DocumentationArea())->listAll();
        $this->render('documentation_areas/index', [
            'title' => 'Áreas Técnicas de Documentação',
            'areas' => $areas,
        ]);
    }

    public function create()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            (new DocumentationArea())->create($name);
            $this->flash('success', 'Área criada com sucesso.');
        } else {
            $this->flash('warning', 'Informe o nome da área.');
        }
        return $this->redirect('/admin/documentation-areas');
    }

    public function update()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id > 0 && $name !== '') {
            (new DocumentationArea())->update($id, $name);
            $this->flash('success', 'Área atualizada.');
        } else {
            $this->flash('warning', 'Dados inválidos para atualizar.');
        }
        return $this->redirect('/admin/documentation-areas');
    }

    public function delete()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $m = new DocumentationArea();
            if ($m->inUse($id)) {
                $this->flash('danger', 'Não é possível excluir: área está em uso por documentações.');
            } else {
                $m->delete($id);
                $this->flash('success', 'Área excluída.');
            }
        } else {
            $this->flash('warning', 'ID inválido.');
        }
        return $this->redirect('/admin/documentation-areas');
    }
}

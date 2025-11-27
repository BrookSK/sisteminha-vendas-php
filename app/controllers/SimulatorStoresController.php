<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\SimulatorStore;

class SimulatorStoresController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $stores = (new SimulatorStore())->all();
        $this->render('simulator_stores/index', [
            'title' => 'Lojas do Simulador',
            'stores' => $stores,
        ]);
    }

    public function create()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $this->flash('danger', 'Nome da loja é obrigatório.');
            return $this->redirect('/admin/simulator-stores');
        }
        (new SimulatorStore())->create($name);
        $this->flash('success', 'Loja criada com sucesso.');
        return $this->redirect('/admin/simulator-stores');
    }

    public function delete()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new SimulatorStore())->delete($id);
            $this->flash('success', 'Loja excluída com sucesso.');
        }
        return $this->redirect('/admin/simulator-stores');
    }
}

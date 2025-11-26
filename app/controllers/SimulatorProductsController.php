<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\SimulatorProduct;
use Models\Approval;
use Models\Notification;
use Models\User;

class SimulatorProductsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $model = new SimulatorProduct();
        // Lista simples (sem paginação avançada)
        $items = $model->searchByName(null, 200, 0);
        $this->render('simulator_products/index', [
            'title' => 'Produtos do Simulador',
            'items' => $items,
        ]);
    }

    // GET /admin/simulator-products/template
    public function template()
    {
        $this->requireRole(['admin']);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="modelo_produtos_simulador.csv"');
        $out = fopen('php://output', 'w');
        // Cabeçalho da planilha modelo
        fputcsv($out, ['sku', 'imagem', 'descricao', 'peso_kg']);
        fclose($out);
        return exit;
    }

    // POST /admin/simulator-products/import
    public function import()
    {
        $this->csrfCheck();
        $this->requireRole(['admin']);

        if (empty($_FILES['arquivo']['tmp_name']) || !is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
            $this->flash('danger', 'Nenhuma planilha enviada.');
            return $this->redirect('/admin/simulator-products');
        }

        $tmp = $_FILES['arquivo']['tmp_name'];
        $handle = fopen($tmp, 'r');
        if (!$handle) {
            $this->flash('danger', 'Não foi possível ler a planilha enviada.');
            return $this->redirect('/admin/simulator-products');
        }

        // Lê cabeçalho
        $header = fgetcsv($handle, 0, ',');
        $map = ['sku' => null, 'imagem' => null, 'descricao' => null, 'peso_kg' => null];
        if (is_array($header)) {
            foreach ($header as $idx => $col) {
                $col = mb_strtolower(trim((string)$col));
                if (array_key_exists($col, $map)) {
                    $map[$col] = $idx;
                }
            }
        }

        $model = new SimulatorProduct();
        $criados = 0; $ignorados = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (!is_array($row) || count($row) === 0) continue;
            $sku = $map['sku'] !== null ? trim((string)($row[$map['sku']] ?? '')) : '';
            $imagem = $map['imagem'] !== null ? trim((string)($row[$map['imagem']] ?? '')) : '';
            $descricao = $map['descricao'] !== null ? trim((string)($row[$map['descricao']] ?? '')) : '';
            $pesoStr = $map['peso_kg'] !== null ? trim((string)($row[$map['peso_kg']] ?? '')) : '';
            if ($descricao === '' && $sku === '') {
                continue; // linha vazia
            }
            $peso = (float)str_replace([','], ['.'], $pesoStr ?: '0');

            // Regra: se já existir produto com mesmo SKU ou mesmo nome, não mexe
            $exists = false;
            if ($sku !== '' || $descricao !== '') {
                $q = 'SELECT id FROM simulator_products WHERE ';
                $conds = [];
                $params = [];
                if ($sku !== '') { $conds[] = 'sku = :sku'; $params[':sku'] = $sku; }
                if ($descricao !== '') { $conds[] = 'nome = :nome'; $params[':nome'] = $descricao; }
                $q .= implode(' OR ', $conds) . ' LIMIT 1';
                $stmt = $model->getDb()->prepare($q);
                $stmt->execute($params);
                $rowExist = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($rowExist) { $exists = true; }
            }

            if ($exists) {
                $ignorados++;
                continue;
            }

            $model->create([
                'sku' => $sku !== '' ? $sku : null,
                'nome' => $descricao !== '' ? $descricao : ($sku ?: 'Produto sem descrição'),
                'marca' => null,
                'image_url' => $imagem !== '' ? $imagem : null,
                'peso_kg' => $peso,
            ], []);
            $criados++;
        }

        fclose($handle);

        $msg = "Importação concluída. Produtos criados: {$criados}. Linhas ignoradas (SKU/nome já existentes ou inválidos): {$ignorados}.";
        $this->flash('success', $msg);
        return $this->redirect('/admin/simulator-products');
    }

    public function new()
    {
        $this->requireRole(['admin']);
        $this->render('simulator_products/form', [
            'title' => 'Novo Produto do Simulador',
            'product' => null,
            'links' => [],
            'action' => '/admin/simulator-products/create',
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function create()
    {
        $this->csrfCheck();
        $this->requireRole(['admin']);
        $in = $_POST;
        $nome = trim($in['nome'] ?? '');
        if ($nome === '') {
            $this->flash('danger', 'Nome do produto é obrigatório.');
            return $this->redirect('/admin/simulator-products/new');
        }
        $marca = trim($in['marca'] ?? '');
        $peso = (float)($in['peso_kg'] ?? 0);
        $links = [];
        if (!empty($in['links']) && is_array($in['links'])) {
            foreach ($in['links'] as $url) {
                $u = trim((string)$url);
                if ($u === '') continue;
                $links[] = ['url' => $u, 'fonte' => null];
            }
        }
        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');
        if ($role === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $payload = [
                'data' => [
                    'nome' => $nome,
                    'marca' => $marca ?: null,
                    'peso_kg' => $peso,
                ],
                'links' => $links,
            ];
            $apprId = (new Approval())->createPending('product', 'create', $payload, (int)($me['id'] ?? 0), $supervisorId, null);
            if ($supervisorId) {
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Aprovação de Produto', 'Um novo produto do simulador foi enviado por um trainee e aguarda sua aprovação. [approval-id:'.$apprId.']', 'approval', 'new', [$supervisorId]);
            }
            $this->flash('info', 'Produto enviado para aprovação do supervisor.');
            return $this->redirect('/admin/simulator-products');
        }

        $model = new SimulatorProduct();
        $id = $model->create([
            'nome' => $nome,
            'marca' => $marca ?: null,
            'peso_kg' => $peso,
        ], $links);
        $this->flash('success', 'Produto criado com sucesso.');
        return $this->redirect('/admin/simulator-products');
    }

    public function edit()
    {
        $this->requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/simulator-products');
        $model = new SimulatorProduct();
        $product = $model->find($id);
        if (!$product) return $this->redirect('/admin/simulator-products');
        $links = $product['links'] ?? [];
        $this->render('simulator_products/form', [
            'title' => 'Editar Produto do Simulador',
            'product' => $product,
            'links' => $links,
            'action' => '/admin/simulator-products/update?id='.$id,
            '_csrf' => Auth::csrf(),
        ]);
    }

    public function update()
    {
        $this->csrfCheck();
        $this->requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/simulator-products');
        $in = $_POST;
        $nome = trim($in['nome'] ?? '');
        if ($nome === '') {
            $this->flash('danger', 'Nome do produto é obrigatório.');
            return $this->redirect('/admin/simulator-products/edit?id='.$id);
        }
        $marca = trim($in['marca'] ?? '');
        $peso = (float)($in['peso_kg'] ?? 0);
        $links = [];
        if (!empty($in['links']) && is_array($in['links'])) {
            foreach ($in['links'] as $url) {
                $u = trim((string)$url);
                if ($u === '') continue;
                $links[] = ['url' => $u, 'fonte' => null];
            }
        }
        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');
        if ($role === 'trainee') {
            $meFull = (new User())->findById((int)($me['id'] ?? 0));
            $supervisorId = (int)($meFull['supervisor_user_id'] ?? 0) ?: null;
            $payload = [
                'id' => $id,
                'data' => [
                    'nome' => $nome,
                    'marca' => $marca ?: null,
                    'peso_kg' => $peso,
                ],
                'links' => $links,
            ];
            $apprId = (new Approval())->createPending('product', 'update', $payload, (int)($me['id'] ?? 0), $supervisorId, $id);
            if ($supervisorId) {
                (new Notification())->createWithUsers((int)($me['id'] ?? 0), 'Edição de Produto', 'Uma edição de produto do simulador foi enviada por um trainee e aguarda sua aprovação. [approval-id:'.$apprId.']', 'approval', 'new', [$supervisorId]);
            }
            $this->flash('info', 'Edição enviada para aprovação.');
            return $this->redirect('/admin/simulator-products');
        }

        $model = new SimulatorProduct();
        $model->update($id, [
            'nome' => $nome,
            'marca' => $marca ?: null,
            'peso_kg' => $peso,
        ], $links);
        $this->flash('success', 'Produto atualizado com sucesso.');
        return $this->redirect('/admin/simulator-products');
    }

    public function delete()
    {
        $this->csrfCheck();
        $this->requireRole(['admin']);
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return $this->redirect('/admin/simulator-products');
        $model = new SimulatorProduct();
        $model->delete($id);
        $this->flash('success', 'Produto excluído com sucesso.');
        return $this->redirect('/admin/simulator-products');
    }
    // GET /admin/sales-simulator/products/search?q=...
    public function search()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);
        $q = trim($_GET['q'] ?? '');
        $rows = (new SimulatorProduct())->searchByName($q !== '' ? $q : null, 20, 0);
        $out = [];
        foreach ($rows as $r) {
            $label = $r['nome'];
            if (!empty($r['marca'])) {
                $label .= ' - '.$r['marca'];
            }
            $out[] = [
                'id' => (int)$r['id'],
                'text' => $label,
                'nome' => $r['nome'],
                'marca' => $r['marca'],
                'peso_kg' => (float)$r['peso_kg'],
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }

    // POST /admin/sales-simulator/products/create-ajax
    public function createAjax()
    {
        $this->csrfCheck();
        $this->requireRole(['seller','trainee','manager','admin','organic']);

        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');
        // Trainee não pode criar produtos diretamente pelo simulador; deve usar a tela própria com aprovação
        if ($role === 'trainee') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'forbidden_for_trainee']);
            return exit;
        }

        $in = $_POST;
        if (empty($in) && (($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json')) {
            $raw = file_get_contents('php://input');
            $in = json_decode($raw, true) ?: [];
        }

        $nome = trim($in['nome'] ?? '');
        $marca = trim($in['marca'] ?? '');
        $peso = (float)($in['peso_kg'] ?? 0);
        $linksIn = $in['links'] ?? [];
        if ($nome === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'nome_obrigatorio']);
            return exit;
        }

        $links = [];
        if (is_array($linksIn)) {
            foreach ($linksIn as $lnk) {
                $url = is_array($lnk) ? ($lnk['url'] ?? '') : (string)$lnk;
                $url = trim($url);
                if ($url === '') continue;
                $fonte = is_array($lnk) ? ($lnk['fonte'] ?? null) : null;
                $links[] = ['url' => $url, 'fonte' => $fonte];
            }
        } elseif (is_string($linksIn)) {
            $url = trim($linksIn);
            if ($url !== '') {
                $links[] = ['url' => $url, 'fonte' => null];
            }
        }

        $model = new SimulatorProduct();
        $id = $model->create([
            'nome' => $nome,
            'marca' => $marca !== '' ? $marca : null,
            'peso_kg' => $peso,
        ], $links);

        $created = $model->find($id);
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $id,
            'nome' => $created['nome'] ?? $nome,
            'marca' => $created['marca'] ?? $marca,
            'peso_kg' => (float)($created['peso_kg'] ?? $peso),
            'links' => $created['links'] ?? $links,
        ]);
        return exit;
    }
}

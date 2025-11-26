<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\SimulatorBudget;
use Models\SimulatorProduct;
use Models\SimulatorProductPurchase;

class SimulatorProductsReportController extends Controller
{
    private function normalizeFreeName(string $name): string
    {
        $name = trim(mb_strtolower($name, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $name);
    }

    public function index()
    {
        $this->requireRole(['manager','admin']);
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        if ($from === '' || $to === '') {
            // padrão: mês atual
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        }
        $q = trim($_GET['q'] ?? '');

        $budgetsModel = new SimulatorBudget();
        $rows = $budgetsModel->listPaidInRange($from, $to);

        $consolidated = [];
        $allProductIds = [];

        foreach ($rows as $row) {
            $data = json_decode($row['data_json'] ?? '[]', true) ?: [];
            $items = $data['items'] ?? [];
            if (!is_array($items) || !$items) continue;
            foreach ($items as $it) {
                $name = trim((string)($it['nome'] ?? ''));
                if ($name === '') continue;
                $qtd = (int)($it['qtd'] ?? 0) ?: 0;
                $peso = (float)($it['peso'] ?? 0.0);
                $valor = (float)($it['valor'] ?? 0.0);
                if ($qtd <= 0 && $peso <= 0 && $valor <= 0) continue;
                $productId = $it['product_id'] ?? null;
                if ($productId) {
                    $key = 'db:'.(int)$productId;
                    $allProductIds[(int)$productId] = true;
                } else {
                    $norm = $this->normalizeFreeName($name);
                    $key = 'free:'.$norm;
                }
                if (!isset($consolidated[$key])) {
                    $consolidated[$key] = [
                        'key' => $key,
                        'product_id' => $productId ? (int)$productId : null,
                        'name' => $name,
                        'total_qtd' => 0,
                        'total_peso' => 0.0,
                        'total_valor' => 0.0,
                        'budget_ids' => [],
                    ];
                }
                $consolidated[$key]['total_qtd'] += $qtd;
                $consolidated[$key]['total_peso'] += max(0.0, $peso) * max(1, $qtd);
                $consolidated[$key]['total_valor'] += max(0.0, $valor) * max(1, $qtd);
                $consolidated[$key]['budget_ids'][(int)$row['id']] = true;
            }
        }

        // Enriquecer com dados da base de produtos, quando houver product_id
        $productsInfo = [];
        if ($allProductIds) {
            $ids = array_keys($allProductIds);
            $simProd = new SimulatorProduct();
            foreach ($ids as $pid) {
                $p = $simProd->find((int)$pid);
                if ($p) {
                    $productsInfo[(int)$pid] = $p;
                }
            }
        }

        // Trazer informações de quantidade comprada
        $keys = array_keys($consolidated);
        $purchases = (new SimulatorProductPurchase())->getForKeys($keys);

        // Montar lista final para view
        $items = [];
        foreach ($consolidated as $key => $row) {
            $pid = $row['product_id'] ? (int)$row['product_id'] : null;
            $info = $pid && isset($productsInfo[$pid]) ? $productsInfo[$pid] : null;
            $purchasedQtd = (int)($purchases[$key] ?? 0);
            $totalQtd = (int)($row['total_qtd'] ?? 0);
            $statusCompra = 'nao_comprado';
            if ($totalQtd > 0) {
                if ($purchasedQtd >= $totalQtd) {
                    $statusCompra = 'comprado_total';
                } elseif ($purchasedQtd > 0) {
                    $statusCompra = 'comprado_parcial';
                }
            }
            $item = [
                'key' => $key,
                'product_id' => $pid,
                'name' => $info['nome'] ?? $row['name'],
                'image_url' => $info['image_url'] ?? null,
                'links' => $info['links'] ?? [],
                'total_qtd' => $totalQtd,
                'total_peso' => (float)$row['total_peso'],
                'total_valor' => (float)$row['total_valor'],
                'budgets_count' => count($row['budget_ids'] ?? []),
                'purchased_qtd' => $purchasedQtd,
                'status_compra' => $statusCompra,
            ];
            if ($q !== '') {
                $needle = mb_strtolower($q, 'UTF-8');
                $hay = mb_strtolower($item['name'] ?? '', 'UTF-8');
                if (mb_strpos($hay, $needle) === false) {
                    continue;
                }
            }
            $items[] = $item;
        }

        usort($items, function($a, $b){
            return strcmp(mb_strtolower($a['name'],'UTF-8'), mb_strtolower($b['name'],'UTF-8'));
        });

        $this->render('sales_simulator/products_report', [
            'title' => 'Relatório de Produtos do Simulador',
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'items' => $items,
        ]);
    }

    public function updatePurchased()
    {
        $this->requireRole(['manager','admin']);
        $this->csrfCheck();
        $key = trim($_POST['product_key'] ?? '');
        if ($key === '') { return $this->redirect('/admin/sales-simulator/products-report'); }
        $qtd = (int)($_POST['purchased_qtd'] ?? 0);
        (new SimulatorProductPurchase())->setPurchasedQty($key, $qtd);
        $this->flash('success', 'Quantidade comprada atualizada para o produto.');
        return $this->redirect('/admin/sales-simulator/products-report');
    }

    public function product()
    {
        $this->requireRole(['manager','admin']);
        $key = trim($_GET['product_key'] ?? '');
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        if ($key === '' || $from === '' || $to === '') {
            return $this->redirect('/admin/sales-simulator/products-report');
        }
        $budgetsModel = new SimulatorBudget();
        $rows = $budgetsModel->listPaidInRange($from, $to);
        $budgets = [];
        foreach ($rows as $row) {
            $data = json_decode($row['data_json'] ?? '[]', true) ?: [];
            $items = $data['items'] ?? [];
            if (!is_array($items) || !$items) continue;
            $found = [];
            foreach ($items as $it) {
                $name = trim((string)($it['nome'] ?? ''));
                if ($name === '') continue;
                $productId = $it['product_id'] ?? null;
                $curKey = $productId ? ('db:'.(int)$productId) : ('free:'.$this->normalizeFreeName($name));
                if ($curKey !== $key) continue;
                $found[] = $it;
            }
            if ($found) {
                $budgets[] = [
                    'id' => (int)$row['id'],
                    'name' => (string)$row['name'],
                    'paid_at' => $row['paid_at'] ?? null,
                    'items' => $found,
                ];
            }
        }
        $this->render('sales_simulator/products_report_product', [
            'title' => 'Orçamentos com o produto',
            'product_key' => $key,
            'from' => $from,
            'to' => $to,
            'budgets' => $budgets,
        ]);
    }

    public function exportPdf()
    {
        $this->requireRole(['manager','admin']);
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        $q = trim($_GET['q'] ?? '');
        if ($from === '' || $to === '') {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        }

        // Reutiliza a lógica de index para montar a lista
        $_GET['from'] = $from;
        $_GET['to'] = $to;
        $_GET['q'] = $q;
        ob_start();
        $this->index();
        $htmlPage = ob_get_clean();

        // Para simplificar, usamos o HTML da página como base de PDF
        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($htmlPage);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('produtos_simulador.pdf', ['Attachment' => true]);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="padding:16px;font-family:Arial, sans-serif">'
            .'<p><strong>Dompdf não encontrado.</strong> Instale com:</p>'
            .'<pre>composer require dompdf/dompdf</pre>'
            .'<hr>'
            .$htmlPage
            .'</div>';
    }
}

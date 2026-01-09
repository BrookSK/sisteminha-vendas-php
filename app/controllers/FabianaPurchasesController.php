<?php
namespace Controllers;

use Core\Controller;
use Models\Setting;
use Models\SimulatorBudget;
use Models\SimulatorProduct;
use Models\SimulatorProductPurchase;
use Models\SimulatorStore;
use Models\SimulatorWebhookProduct;

class FabianaPurchasesController extends Controller
{
    public function index()
    {
        $this->requireRole(['manager','admin']);

        $setting = new Setting();
        [$defaultFrom, $defaultTo] = $setting->currentPeriod();
        $from = trim($_GET['from'] ?? $defaultFrom);
        $to = trim($_GET['to'] ?? $defaultTo);
        if ($from === '' || $to === '') {
            $from = $defaultFrom;
            $to = $defaultTo;
        }
        $storeFilter = trim($_GET['store_id'] ?? '');

        $budgetsModel = new SimulatorBudget();
        $rows = $budgetsModel->listPaidInRange($from, $to);

        $webhookRows = (new SimulatorWebhookProduct())->listInRange($from, $to);

        $consolidated = [];
        $allProductIds = [];
        $storeIds = [];

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
                    // para a Fabiana focamos apenas em produtos do banco
                    continue;
                }
                if (!isset($consolidated[$key])) {
                    $consolidated[$key] = [
                        'key' => $key,
                        'product_id' => $productId ? (int)$productId : null,
                        'name' => $name,
                        'total_qtd' => 0,
                        'total_peso' => 0.0,
                        'total_valor' => 0.0,
                    ];
                }
                $consolidated[$key]['total_qtd'] += $qtd;
                $consolidated[$key]['total_peso'] += max(0.0, $peso) * max(1, $qtd);
                $consolidated[$key]['total_valor'] += max(0.0, $valor) * max(1, $qtd);
            }
        }

        foreach ($webhookRows as $wr) {
            $externalId = trim((string)($wr['external_id'] ?? ''));
            $name = trim((string)($wr['nome'] ?? ''));
            if ($externalId === '' || $name === '') {
                continue;
            }

            $key = 'wh:'.$externalId;
            $qtd = (int)($wr['qtd'] ?? 0);
            if ($qtd < 0) {
                $qtd = 0;
            }
            $pesoKg = (float)($wr['peso_kg'] ?? 0.0);
            if ($pesoKg < 0) {
                $pesoKg = 0.0;
            }
            $valorUsd = (float)($wr['valor_usd'] ?? 0.0);
            if ($valorUsd < 0) {
                $valorUsd = 0.0;
            }

            if (!isset($consolidated[$key])) {
                $consolidated[$key] = [
                    'key' => $key,
                    'product_id' => null,
                    'name' => $name,
                    'total_qtd' => 0,
                    'total_peso' => 0.0,
                    'total_valor' => 0.0,
                    'webhook' => [
                        'image_url' => $wr['image_url'] ?? null,
                        'store_id' => $wr['store_id'] !== null ? (int)$wr['store_id'] : null,
                        'store_name' => $wr['store_name'] ?? null,
                        'links_json' => $wr['links_json'] ?? null,
                    ],
                ];
            }

            $consolidated[$key]['name'] = $name;
            $consolidated[$key]['total_qtd'] += $qtd;
            $consolidated[$key]['total_peso'] += max(0.0, $pesoKg) * max(1, $qtd);
            $consolidated[$key]['total_valor'] += max(0.0, $valorUsd) * max(1, $qtd);
        }

        $productsInfo = [];
        if ($allProductIds) {
            $ids = array_keys($allProductIds);
            $simProd = new SimulatorProduct();
            foreach ($ids as $pid) {
                $p = $simProd->find((int)$pid);
                if ($p) {
                    $productsInfo[(int)$pid] = $p;
                    if (!empty($p['store_id'])) {
                        $storeIds[(int)$p['store_id']] = true;
                    }
                }
            }
        }

        $storesMap = [];
        if ($storeIds) {
            $stores = (new SimulatorStore())->all();
            foreach ($stores as $st) {
                $sid = (int)($st['id'] ?? 0);
                if ($sid && isset($storeIds[$sid])) {
                    $storesMap[$sid] = (string)($st['name'] ?? '');
                }
            }
        }

        $keys = array_keys($consolidated);
        $purchaseModel = new SimulatorProductPurchase();
        $purchases = $purchaseModel->getForKeys($keys);

        $items = [];
        foreach ($consolidated as $key => $row) {
            $pid = $row['product_id'] ? (int)$row['product_id'] : null;
            $info = $pid && isset($productsInfo[$pid]) ? $productsInfo[$pid] : null;
            $totalQtd = (int)($row['total_qtd'] ?? 0);
            if ($totalQtd <= 0) continue;
            $purchasedQtd = (int)($purchases[$key] ?? 0);

            $storeId = null;
            if ($info && array_key_exists('store_id', $info)) {
                $storeId = $info['store_id'] !== null ? (int)$info['store_id'] : null;
            }

            $imageUrl = $info['image_url'] ?? null;
            $links = $info['links'] ?? [];
            $storeName = null;
            if ($storeId && isset($storesMap[$storeId])) {
                $storeName = $storesMap[$storeId];
            }

            if (!$info && isset($row['webhook']) && is_array($row['webhook'])) {
                if ($storeId === null && array_key_exists('store_id', $row['webhook'])) {
                    $storeId = $row['webhook']['store_id'] !== null ? (int)$row['webhook']['store_id'] : null;
                }
                if ($storeName === null && !empty($row['webhook']['store_name'])) {
                    $storeName = (string)$row['webhook']['store_name'];
                }
                if ($imageUrl === null && !empty($row['webhook']['image_url'])) {
                    $imageUrl = (string)$row['webhook']['image_url'];
                }
                if (empty($links) && !empty($row['webhook']['links_json'])) {
                    $decodedLinks = json_decode((string)$row['webhook']['links_json'], true);
                    if (is_array($decodedLinks)) {
                        $links = $decodedLinks;
                    }
                }
            }

            if ($storeFilter !== '') {
                if ($storeFilter === '0') {
                    if (!empty($storeId)) {
                        continue;
                    }
                } else {
                    $storeFilterInt = (int)$storeFilter;
                    if ((int)($storeId ?? 0) !== $storeFilterInt) {
                        continue;
                    }
                }
            }
            $totalValor = (float)$row['total_valor'];
            $unitValor = $totalQtd > 0 ? ($totalValor / $totalQtd) : 0.0;
            $purchasedQtdClamped = max(0, min($totalQtd, $purchasedQtd));
            $remainingQtd = max(0, $totalQtd - $purchasedQtdClamped);
            $remainingValor = $unitValor * $remainingQtd;

            $items[] = [
                'key' => $key,
                'product_id' => $pid,
                'name' => $info['nome'] ?? $row['name'],
                'image_url' => $imageUrl,
                'links' => $links,
                'store_id' => $storeId,
                'store_name' => $storeName,
                'total_qtd' => $totalQtd,
                'purchased_qtd' => $purchasedQtdClamped,
                'remaining_qtd' => $remainingQtd,
                'total_valor' => $totalValor,
                'remaining_valor' => $remainingValor,
            ];
        }

        usort($items, function($a, $b){
            return strcmp(mb_strtolower($a['name'],'UTF-8'), mb_strtolower($b['name'],'UTF-8'));
        });

        $allStores = (new SimulatorStore())->all();

        $totalNeeded = 0.0;
        $totalRemaining = 0.0;
        foreach ($items as $it) {
            $totalNeeded += (float)($it['total_valor'] ?? 0);
            $totalRemaining += (float)($it['remaining_valor'] ?? 0);
        }

        $fabianaCashTotal = (float)$setting->get('fabiana_cash_total_usd', '0');
        $totalToSend = max(0.0, $totalRemaining - $fabianaCashTotal);

        $this->render('sales_simulator/fabiana_dashboard', [
            'title' => 'Dashboard de Compras - Fabiana',
            'from' => $from,
            'to' => $to,
            'store_id' => $storeFilter,
            'items' => $items,
            'stores' => $allStores,
            'total_needed_usd' => $totalNeeded,
            'total_remaining_usd' => $totalRemaining,
            'total_to_send_usd' => $totalToSend,
            'fabiana_cash_total_usd' => $fabianaCashTotal,
        ]);
    }

    public function saveCashTotal()
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $raw = str_replace([','], ['.'], (string)($_POST['fabiana_cash_total_usd'] ?? '0'));
        $amount = (float)$raw;
        if ($amount < 0) $amount = 0.0;
        (new Setting())->set('fabiana_cash_total_usd', (string)$amount);
        $this->flash('success', 'Saldo total em caixa com a Fabiana atualizado.');
        return $this->redirect('/admin/sales-simulator/fabiana');
    }
}

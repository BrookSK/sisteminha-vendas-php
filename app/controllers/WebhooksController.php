<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\Setting;
use Models\WebhookLog;
use Models\Container;
use Models\Log;
use Models\Demand;
use Models\SimulatorWebhookProduct;

class WebhooksController extends Controller
{
    // Webhooks agora não exigem autenticação; apenas recebem JSON bruto.

    public function containers()
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        $logger = new WebhookLog();
        if (!is_array($payload)) { $logger->create('containers','erro','JSON inválido', ['raw'=>$raw]); http_response_code(400); echo 'Invalid JSON'; return; }

        // Campos esperados mínimos
        $required = ['id','codigo_utilizador','peso_kg','status','valor_transporte','data_criacao'];
        foreach ($required as $k) { if (!array_key_exists($k, $payload)) { $logger->create('containers','erro','Campo faltando: '.$k, $payload); http_response_code(400); echo 'Missing '.$k; return; } }

        // Upsert container pelo invoice_id (id da fatura) ou pelo próprio id do payload
        $data = [
            'invoice_id' => $payload['id'],
            'utilizador_id' => $payload['codigo_utilizador'] ?? null,
            'peso_kg' => (float)$payload['peso_kg'],
            'status' => $payload['status'] ?? 'Em preparo',
            'transporte_mercadoria_usd' => (float)($payload['valor_transporte'] ?? 0),
            'created_at' => $payload['data_criacao'] ?? date('Y-m-d'),
        ];
        // Busca existente por invoice_id
        $db = Database::pdo();
        $st = $db->prepare('SELECT id FROM containers WHERE invoice_id = :inv LIMIT 1');
        $st->execute([':inv'=>$data['invoice_id']]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            (new Container())->update((int)$row['id'], $data);
            $logger->create('containers','atualizacao','Container atualizado', $payload);
        } else {
            $id = (new Container())->create($data);
            $logger->create('containers','sucesso','Container criado id='.$id, $payload);
        }

        (new Log())->add(null,'webhook','containers',null,json_encode($payload));
        echo 'OK';
    }

    public function simulatorProducts()
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        $logger = new WebhookLog();
        if (!is_array($payload)) {
            $logger->create('simulator_products', 'erro', 'JSON inválido', ['raw' => $raw]);
            http_response_code(400);
            echo 'Invalid JSON';
            return;
        }

        $required = ['id', 'nome', 'qtd', 'peso_kg', 'valor_usd', 'data'];
        foreach ($required as $k) {
            if (!array_key_exists($k, $payload)) {
                $logger->create('simulator_products', 'erro', 'Campo faltando: ' . $k, $payload);
                http_response_code(400);
                echo 'Missing ' . $k;
                return;
            }
        }

        $externalId = trim((string)$payload['id']);
        $nome = trim((string)$payload['nome']);
        if ($externalId === '' || $nome === '') {
            $logger->create('simulator_products', 'erro', 'id/nome inválidos', $payload);
            http_response_code(400);
            echo 'Invalid id/nome';
            return;
        }

        $qtd = (int)($payload['qtd'] ?? 0);
        if ($qtd < 0) {
            $qtd = 0;
        }

        $pesoKg = (float)($payload['peso_kg'] ?? 0);
        if ($pesoKg < 0) {
            $pesoKg = 0.0;
        }

        $valorUsd = (float)($payload['valor_usd'] ?? 0);
        if ($valorUsd < 0) {
            $valorUsd = 0.0;
        }

        $imageUrl = isset($payload['image_url']) ? trim((string)$payload['image_url']) : '';
        if ($imageUrl === '') {
            $imageUrl = null;
        }

        $storeId = null;
        if (array_key_exists('store_id', $payload)) {
            $storeIdInt = (int)$payload['store_id'];
            if ($storeIdInt > 0) {
                $storeId = $storeIdInt;
            }
        }

        $storeName = isset($payload['store_name']) ? trim((string)$payload['store_name']) : '';
        if ($storeName === '') {
            $storeName = null;
        }

        $linksJson = null;
        if (array_key_exists('links', $payload)) {
            $links = $payload['links'];
            if (is_array($links)) {
                $linksJson = json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $eventDate = trim((string)$payload['data']);
        if ($eventDate === '') {
            $eventDate = null;
        }

        try {
            $dbp = Database::pdoProducts();
            $st = $dbp->prepare('SELECT id FROM simulator_webhook_products WHERE external_id = :eid LIMIT 1');
            $st->execute([':eid' => $externalId]);
            $exists = (bool)$st->fetch(\PDO::FETCH_ASSOC);

            (new SimulatorWebhookProduct())->upsert([
                'external_id' => $externalId,
                'nome' => $nome,
                'image_url' => $imageUrl,
                'store_id' => $storeId,
                'store_name' => $storeName,
                'qtd' => $qtd,
                'peso_kg' => $pesoKg,
                'valor_usd' => $valorUsd,
                'links_json' => $linksJson,
                'event_date' => $eventDate,
            ]);

            $logger->create('simulator_products', $exists ? 'atualizacao' : 'sucesso', $exists ? 'Produto atualizado' : 'Produto criado', $payload);
            (new Log())->add(null, 'webhook', 'simulator_products', null, json_encode($payload));
            echo 'OK';
        } catch (\Throwable $e) {
            $logger->create('simulator_products', 'erro', $e->getMessage(), $payload);
            http_response_code(500);
            echo $e->getMessage();
        }
    }

    public function sales()
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        $logger = new WebhookLog();
        if (!is_array($payload)) { $logger->create('sales','erro','JSON inválido', ['raw'=>$raw]); http_response_code(400); echo 'Invalid JSON'; return; }

        // Campos mínimos comuns
        $required = ['id','tipo','cliente_id','valor_bruto_usd','valor_liquido_usd','peso_kg','data'];
        foreach ($required as $k) { if (!array_key_exists($k, $payload)) { $logger->create('sales','erro','Campo faltando: '.$k, $payload); http_response_code(400); echo 'Missing '.$k; return; } }
        $tipo = $payload['tipo']; // 'intl' | 'nat'
        try {
            // Resolve vendedor_id (opcional no payload).
            $db = Database::pdo();
            $vendedorId = null;
            if (isset($payload['vendedor_id']) && (int)$payload['vendedor_id'] > 0) {
                $vendedorId = (int)$payload['vendedor_id'];
            } else {
                // tenta admin ativo
                $q = $db->query("SELECT id FROM usuarios WHERE role='admin' AND (ativo=1 OR ativo IS NULL) ORDER BY id LIMIT 1");
                $r = $q ? $q->fetch(\PDO::FETCH_ASSOC) : false;
                if ($r && (int)$r['id'] > 0) {
                    $vendedorId = (int)$r['id'];
                } else {
                    // fallback: qualquer usuário existente
                    $q2 = $db->query("SELECT id FROM usuarios ORDER BY id LIMIT 1");
                    $r2 = $q2 ? $q2->fetch(\PDO::FETCH_ASSOC) : false;
                    if ($r2 && (int)$r2['id'] > 0) { $vendedorId = (int)$r2['id']; }
                }
            }

            // Verifica cliente_id existe; se não, tenta fallback para algum cliente existente
            $clienteId = (int)$payload['cliente_id'];
            $hasClient = false;
            $chk = $db->prepare('SELECT id FROM clientes WHERE id = :cid LIMIT 1');
            if ($chk->execute([':cid'=>$clienteId])) {
                $hasClient = (bool)$chk->fetch(\PDO::FETCH_ASSOC);
            }
            if (!$hasClient) {
                $alt = $db->query('SELECT id FROM clientes ORDER BY id LIMIT 1');
                $rowc = $alt ? $alt->fetch(\PDO::FETCH_ASSOC) : false;
                if ($rowc && (int)$rowc['id']>0) {
                    $msg = 'Cliente inexistente: '.(string)$clienteId.'; usando fallback id='.(string)$rowc['id'];
                    $logger->create('sales','aviso',$msg, $payload);
                    $clienteId = (int)$rowc['id'];
                } else {
                    // Sem cliente para referenciar
                    $msg = 'Cliente inexistente: '.(string)$clienteId.' e não há cliente para fallback.';
                    $logger->create('sales','erro',$msg, $payload);
                    http_response_code(400); echo $msg; return;
                }
            }

            // Taxa de câmbio e totais em BRL
            $rate = 0.0;
            try { $set = new Setting(); $rate = (float)$set->get('usd_rate','5.83'); } catch (\Throwable $e) { $rate = 5.83; }
            if ($rate <= 0) { $rate = 5.83; }
            $bruto_usd = (float)$payload['valor_bruto_usd'];
            $liq_usd = (float)$payload['valor_liquido_usd'];
            $bruto_brl = $bruto_usd * $rate;
            $liq_brl = $liq_usd * $rate;

            // Comissão (replica regra dos modelos)
            $ym = date('Y-m', strtotime($payload['data']));
            if ($tipo === 'intl') {
                $rowSeller = $db->prepare("SELECT COALESCE(SUM(total_bruto_usd),0) b FROM vendas_internacionais WHERE vendedor_id=:sid AND DATE_FORMAT(data_lancamento,'%Y-%m') = :ym");
                $rowSeller->execute([':sid'=>$vendedorId, ':ym'=>$ym]);
                $seller_bruto = (float)($rowSeller->fetch(\PDO::FETCH_ASSOC)['b'] ?? 0);
                $rowTeam = $db->prepare("SELECT COALESCE(SUM(total_bruto_usd),0) t FROM vendas_internacionais WHERE DATE_FORMAT(data_lancamento,'%Y-%m') = :ym");
                $rowTeam->execute([':ym'=>$ym]);
                $team_bruto = (float)($rowTeam->fetch(\PDO::FETCH_ASSOC)['t'] ?? 0);
            } else {
                $rowSeller = $db->prepare("SELECT COALESCE(SUM(total_bruto_usd),0) b FROM vendas_nacionais WHERE vendedor_id=:sid AND DATE_FORMAT(data_lancamento,'%Y-%m') = :ym");
                $rowSeller->execute([':sid'=>$vendedorId, ':ym'=>$ym]);
                $seller_bruto = (float)($rowSeller->fetch(\PDO::FETCH_ASSOC)['b'] ?? 0);
                $rowTeam = $db->prepare("SELECT COALESCE(SUM(total_bruto_usd),0) t FROM vendas_nacionais WHERE DATE_FORMAT(data_lancamento,'%Y-%m') = :ym");
                $rowTeam->execute([':ym'=>$ym]);
                $team_bruto = (float)($rowTeam->fetch(\PDO::FETCH_ASSOC)['t'] ?? 0);
            }
            $percent = $seller_bruto <= 30000.0 ? 0.15 : 0.25;
            $bonus_brl = 0.0;
            if ($team_bruto >= 50000.0) {
                $rowActive = $db->query("SELECT COUNT(*) c FROM usuarios WHERE ativo=1 AND role IN ('seller','manager','admin')")->fetch(\PDO::FETCH_ASSOC) ?: [];
                $active = max(1, (int)($rowActive['c'] ?? 0));
                $bonus_brl = ($liq_brl * 0.05) / $active;
            }
            $com_brl = ($liq_brl * $percent) + $bonus_brl;
            $com_usd = $rate > 0 ? ($com_brl / $rate) : 0.0;

            if ($tipo === 'intl') {
                // vendas_internacionais
                // upsert por id_externo
                $st = $db->prepare('SELECT id FROM vendas_internacionais WHERE id_externo = :eid LIMIT 1');
                $st->execute([':eid' => $payload['id']]);
                $exists = $st->fetch(\PDO::FETCH_ASSOC);
                if ($exists) {
                    $up = $db->prepare('UPDATE vendas_internacionais SET cliente_id=:cid, vendedor_id=:vid, total_bruto_usd=:b1, total_bruto_brl=:bb1, total_liquido_usd=:l1, total_liquido_brl=:lb1, comissao_usd=:cu, comissao_brl=:cb, exchange_rate_used=:er, gross_usd=:b2, gross_brl=:bb2, liquid_usd=:l2, liquid_brl=:lb2, peso_kg=:p, data_lancamento=:d WHERE id = :id');
                    $up->execute([':cid'=>$clienteId,':vid'=>$vendedorId,':b1'=>$bruto_usd,':bb1'=>$bruto_brl,':l1'=>$liq_usd,':lb1'=>$liq_brl,':cu'=>$com_usd,':cb'=>$com_brl,':er'=>$rate,':b2'=>$bruto_usd,':bb2'=>$bruto_brl,':l2'=>$liq_usd,':lb2'=>$liq_brl,':p'=>$payload['peso_kg'],':d'=>$payload['data'],':id'=>$exists['id']]);
                    $logger->create('sales','atualizacao','Venda intl atualizada', $payload);
                } else {
                    $ins = $db->prepare('INSERT INTO vendas_internacionais (id_externo, cliente_id, vendedor_id, total_bruto_usd, total_bruto_brl, total_liquido_usd, total_liquido_brl, comissao_usd, comissao_brl, exchange_rate_used, gross_usd, gross_brl, liquid_usd, liquid_brl, peso_kg, data_lancamento) VALUES (:eid,:cid,:vid,:b1,:bb1,:l1,:lb1,:cu,:cb,:er,:b2,:bb2,:l2,:lb2,:p,:d)');
                    $ins->execute([':eid'=>$payload['id'],':cid'=>$clienteId,':vid'=>$vendedorId,':b1'=>$bruto_usd,':bb1'=>$bruto_brl,':l1'=>$liq_usd,':lb1'=>$liq_brl,':cu'=>$com_usd,':cb'=>$com_brl,':er'=>$rate,':b2'=>$bruto_usd,':bb2'=>$bruto_brl,':l2'=>$liq_usd,':lb2'=>$liq_brl,':p'=>$payload['peso_kg'],':d'=>$payload['data']]);
                    $logger->create('sales','sucesso','Venda intl criada', $payload);
                }
            } elseif ($tipo === 'nat') {
                // vendas_nacionais
                $st = $db->prepare('SELECT id FROM vendas_nacionais WHERE id_externo = :eid LIMIT 1');
                $st->execute([':eid' => $payload['id']]);
                $exists = $st->fetch(\PDO::FETCH_ASSOC);
                if ($exists) {
                    $up = $db->prepare('UPDATE vendas_nacionais SET cliente_id=:cid, vendedor_id=:vid, total_bruto_usd=:b1, total_bruto_brl=:bb1, total_liquido_usd=:l1, total_liquido_brl=:lb1, comissao_usd=:cu, comissao_brl=:cb, exchange_rate_used=:er, gross_usd=:b2, gross_brl=:bb2, liquid_usd=:l2, liquid_brl=:lb2, peso_kg=:p, data_lancamento=:d WHERE id = :id');
                    $up->execute([':cid'=>$clienteId,':vid'=>$vendedorId,':b1'=>$bruto_usd,':bb1'=>$bruto_brl,':l1'=>$liq_usd,':lb1'=>$liq_brl,':cu'=>$com_usd,':cb'=>$com_brl,':er'=>$rate,':b2'=>$bruto_usd,':bb2'=>$bruto_brl,':l2'=>$liq_usd,':lb2'=>$liq_brl,':p'=>$payload['peso_kg'],':d'=>$payload['data'],':id'=>$exists['id']]);
                    $logger->create('sales','atualizacao','Venda nat atualizada', $payload);
                } else {
                    $ins = $db->prepare('INSERT INTO vendas_nacionais (id_externo, cliente_id, vendedor_id, total_bruto_usd, total_bruto_brl, total_liquido_usd, total_liquido_brl, comissao_usd, comissao_brl, exchange_rate_used, gross_usd, gross_brl, liquid_usd, liquid_brl, peso_kg, data_lancamento) VALUES (:eid,:cid,:vid,:b1,:bb1,:l1,:lb1,:cu,:cb,:er,:b2,:bb2,:l2,:lb2,:p,:d)');
                    $ins->execute([':eid'=>$payload['id'],':cid'=>$clienteId,':vid'=>$vendedorId,':b1'=>$bruto_usd,':bb1'=>$bruto_brl,':l1'=>$liq_usd,':lb1'=>$liq_brl,':cu'=>$com_usd,':cb'=>$com_brl,':er'=>$rate,':b2'=>$bruto_usd,':bb2'=>$bruto_brl,':l2'=>$liq_usd,':lb2'=>$liq_brl,':p'=>$payload['peso_kg'],':d'=>$payload['data']]);
                    $logger->create('sales','sucesso','Venda nat criada', $payload);
                }
            } else {
                $logger->create('sales','erro','tipo inválido', $payload);
                http_response_code(400); echo 'Invalid tipo'; return;
            }
            (new Log())->add(null,'webhook','sales',null,json_encode($payload));
            echo 'OK';
        } catch (\Throwable $e) {
            $logger->create('sales','erro',$e->getMessage(), $payload);
            http_response_code(500); echo $e->getMessage();
        }
    }

    public function demands()
    {
        // Cria demanda a partir de JSON (sem autenticação)
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        $logger = new WebhookLog();
        if (!is_array($payload)) { $logger->create('demands','erro','JSON inválido', ['raw'=>$raw]); http_response_code(400); echo 'Invalid JSON'; return; }
        $required = ['title','type_desc'];
        foreach ($required as $k) {
            if (!array_key_exists($k, $payload)) { $logger->create('demands','erro','Campo faltando: '.$k, $payload); http_response_code(400); echo 'Missing '.$k; return; }
        }
        try {
            $db = Database::pdo();
            // FK-safe project
            $projectId = isset($payload['project_id']) ? (int)$payload['project_id'] : null;
            if ($projectId) {
                $chk = $db->prepare('SELECT id FROM projects WHERE id=:id LIMIT 1');
                $chk->execute([':id'=>$projectId]);
                if (!$chk->fetch(\PDO::FETCH_ASSOC)) { $projectId = null; }
            }
            // FK-safe assignee (fallback para qualquer usuário)
            $assigneeId = isset($payload['assignee_id']) ? (int)$payload['assignee_id'] : null;
            if ($assigneeId) {
                $ua = $db->prepare('SELECT id FROM usuarios WHERE id=:id LIMIT 1');
                $ua->execute([':id'=>$assigneeId]);
                if (!$ua->fetch(\PDO::FETCH_ASSOC)) { $assigneeId = null; }
            }
            if (!$assigneeId) {
                $alt = $db->query("SELECT id FROM usuarios ORDER BY id LIMIT 1");
                $r = $alt ? $alt->fetch(\PDO::FETCH_ASSOC) : false;
                if ($r && (int)$r['id']>0) { $assigneeId = (int)$r['id']; }
            }

            $data = [
                'title' => (string)$payload['title'],
                'type_desc' => (string)$payload['type_desc'],
                'assignee_id' => $assigneeId,
                'project_id' => $projectId,
                'status' => (string)($payload['status'] ?? 'pendente'),
                'due_date' => $payload['due_date'] ?? null,
                'priority' => (string)($payload['priority'] ?? 'baixa'),
                'classification' => $payload['classification'] ?? null,
                'details' => $payload['details'] ?? null,
            ];
            // Define created_by de forma segura
            $createdBy = null;
            if ($assigneeId) { $createdBy = $assigneeId; }
            if ($createdBy === null) {
                // tenta admin ativo
                $q = $db->query("SELECT id FROM usuarios WHERE role='admin' AND (ativo=1 OR ativo IS NULL) ORDER BY id LIMIT 1");
                $r = $q ? $q->fetch(\PDO::FETCH_ASSOC) : false;
                if ($r && (int)$r['id']>0) { $createdBy = (int)$r['id']; }
            }
            if ($createdBy === null) {
                $q2 = $db->query("SELECT id FROM usuarios ORDER BY id LIMIT 1");
                $r2 = $q2 ? $q2->fetch(\PDO::FETCH_ASSOC) : false;
                if ($r2 && (int)$r2['id']>0) { $createdBy = (int)$r2['id']; }
            }
            $id = (new Demand())->create($data, $createdBy);
            $logger->create('demands','sucesso','Demanda criada id='.$id, $payload);
            (new Log())->add(null,'webhook','demands',null,json_encode($payload));
            echo 'OK';
        } catch (\Throwable $e) {
            $logger->create('demands','erro',$e->getMessage(), $payload);
            http_response_code(500); echo $e->getMessage();
        }
    }

    public function guide()
    {
        $this->requireRole(['admin']);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme.'://'.$host;
        $this->render('webhooks/guide', [
            'title' => 'Guia de Webhooks',
            'base' => $base,
        ]);
    }

    public function index()
    {
        $this->requireRole(['manager','admin']);
        $logs = (new WebhookLog())->list(200, 0);
        $this->render('webhooks/index', [
            'title' => 'Logs de Webhooks',
            'logs' => $logs,
        ]);
    }
}

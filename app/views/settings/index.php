<?php use Core\Auth; ?>
<div class="row">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title m-0">Configurações</h5>
          <a class="btn btn-sm btn-outline-secondary" href="/admin/settings/calculations">Cálculos e Fórmulas</a>
        </div>
        <form method="post" action="/admin/settings/save">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
          <div class="mb-3">
            <label class="form-label">Taxa do Dólar (USD → BRL)</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="number" step="0.01" min="0.01" class="form-control" name="usd_rate" value="<?= htmlspecialchars((string)$rate) ?>" required>
            </div>
            <div class="form-text">Usada para conversão automática nos cálculos.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Embalagem por KG (USD)</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" step="0.01" min="0" class="form-control" name="embalagem_usd_por_kg" value="<?= htmlspecialchars((string)($embalagem ?? '9.70')) ?>" required>
            </div>
            <div class="form-text">Cobrança por kg quando peso > 0 (padrão 9.70 USD/kg).</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Taxa de Custo Global (0 a 1)</label>
            <div class="input-group">
              <span class="input-group-text">%</span>
              <input type="number" step="0.01" min="0" max="1" class="form-control" name="cost_rate" value="<?= htmlspecialchars((string)($cost_rate ?? '0.15')) ?>" required>
            </div>
            <div class="form-text">Percentual (decimal) aplicado sobre o bruto da equipe (ex.: 0.15 = 15%).</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Início do período corrente</label>
            <input type="date" class="form-control" name="current_period_start" value="<?= htmlspecialchars((string)($period_start ?? '')) ?>">
            <div class="form-text">Se vazio, o sistema usa período automático do dia 10 ao dia 9.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Fim do período corrente</label>
            <input type="date" class="form-control" name="current_period_end" value="<?= htmlspecialchars((string)($period_end ?? '')) ?>">
            <div class="form-text">Defina a data final (inclusive) do período corrente.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Conversão Libras por KG</label>
            <div class="input-group">
              <span class="input-group-text">lbs/kg</span>
              <input type="number" step="0.01" min="0.01" class="form-control" name="lbs_per_kg" value="<?= htmlspecialchars((string)($lbs_per_kg ?? '2.2')) ?>" required>
            </div>
            <div class="form-text">Usado para cálculo de containers (padrão 2.2 lbs por kg).</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Tempo de Sessão (segundos)</label>
            <input type="number" step="60" min="1800" class="form-control" name="session_lifetime" value="<?= htmlspecialchars((string)($session_lifetime ?? '28800')) ?>" required>
            <div class="form-text">Duração do login antes de expirar (mínimo 1800s = 30min).</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Senha para Debug de Comissões</label>
            <input type="password" class="form-control" name="commissions_debug_password" value="<?= htmlspecialchars((string)($commissions_debug_password ?? '')) ?>" placeholder="Defina uma senha para proteger o debug">
            <div class="form-text">Se definido, o acesso a /admin/commissions/debug exigirá esta senha (por sessão).</div>
          </div>

          <hr class="my-4">
          <h6 class="mb-3">Integrações • Webhooks</h6>
          <div class="mb-3">
            <label class="form-label">Webhook de Containers (URL de recebimento)</label>
            <input type="url" class="form-control" name="webhook_containers_url" value="<?= htmlspecialchars((string)($webhook_containers_url ?? '')) ?>" placeholder="https://seu-dominio/webhooks/containers">
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="testWebhookContainers">Testar Webhook</button>
              <small id="testWebhookContainersResult" class="text-muted"></small>
            </div>
            <pre class="mt-2 small bg-light p-2 border rounded"><code>{
  "id": "INV-123",
  "codigo_utilizador": "US456",
  "peso_kg": "320",
  "status": "Em Preparo",
  "valor_transporte": "120.00",
  "data_criacao": "2025-10-13"
}</code></pre>
          </div>

          <div class="mb-3">
            <label class="form-label">Webhook de Vendas (URL de recebimento)</label>
            <input type="url" class="form-control" name="webhook_sales_url" value="<?= htmlspecialchars((string)($webhook_sales_url ?? '')) ?>" placeholder="https://seu-dominio/webhooks/sales">
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="testWebhookSales">Testar Webhook</button>
              <small id="testWebhookSalesResult" class="text-muted"></small>
            </div>
            <pre class="mt-2 small bg-light p-2 border rounded"><code>{
  "id": "CRM-ORDER-999",
  "tipo": "intl",
  "cliente_id": 123,
  "valor_bruto_usd": 150.50,
  "valor_liquido_usd": 120.40,
  "peso_kg": 3.2,
  "data": "2025-10-13"
}</code></pre>
          </div>

          <div class="mb-3">
            <label class="form-label">Token Secreto (Bearer)</label>
            <input type="text" class="form-control" name="webhook_secret_token" value="<?= htmlspecialchars((string)($webhook_secret_token ?? '')) ?>" placeholder="Informe um token seguro">
            <div class="form-text">Será validado no header Authorization: Bearer &lt;token&gt; em cada requisição.</div>
          </div>
          <button class="btn btn-primary" type="submit">Salvar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  async function postJson(url, token, json, outEl){
    try{
      outEl.textContent = 'Enviando...';
      const res = await fetch(url, { method:'POST', headers:{ 'Content-Type':'application/json', 'Authorization':'Bearer '+(token||'') }, body: JSON.stringify(json)});
      outEl.textContent = 'Status: '+res.status;
    }catch(e){ outEl.textContent = 'Falha: '+e; }
  }
  const btnC = document.getElementById('testWebhookContainers');
  const btnS = document.getElementById('testWebhookSales');
  if (btnC){
    btnC.addEventListener('click', function(){
      const url = document.querySelector('input[name="webhook_containers_url"]').value;
      const token = document.querySelector('input[name="webhook_secret_token"]').value;
      const out = document.getElementById('testWebhookContainersResult');
      const payload = { id:'INV-123', codigo_utilizador:'US456', peso_kg:'320', status:'Em Preparo', valor_transporte:'120.00', data_criacao:'2025-10-13' };
      postJson(url, token, payload, out);
    });
  }
  if (btnS){
    btnS.addEventListener('click', function(){
      const url = document.querySelector('input[name="webhook_sales_url"]').value;
      const token = document.querySelector('input[name="webhook_secret_token"]').value;
      const out = document.getElementById('testWebhookSalesResult');
      const payload = { id:'CRM-ORDER-999', tipo:'intl', cliente_id:123, valor_bruto_usd:150.50, valor_liquido_usd:120.40, peso_kg:3.2, data:'2025-10-13' };
      postJson(url, token, payload, out);
    });
  }
})();
</script>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">Dashboard</h4>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Período: <?= htmlspecialchars($period_from ?? '') ?> a <?= htmlspecialchars($period_to ?? '') ?></span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
  </div>
</div>
<?php $role = (string) ((\Core\Auth::user()['role'] ?? 'seller')); ?>
<div class="mb-3 d-flex flex-wrap gap-2">
  <?php if ($role === 'organic'): ?>
    <a class="btn btn-outline-secondary" href="/admin/documentations">Documentações</a>
  <?php else: ?>
    <a class="btn btn-outline-primary" href="/admin/demands/dashboard">Demandas</a>
    <a class="btn btn-outline-secondary" href="/admin/documentations">Documentações</a>
    <?php if ($role === 'admin'): ?>
      <a class="btn btn-outline-secondary" href="/admin/hostings">Hospedagens</a>
      <a class="btn btn-outline-secondary" href="/admin/hosting-assets">Ativos</a>
      <a class="btn btn-outline-secondary" href="/admin/site-clients">Clientes (Sites)</a>
      <a class="btn btn-outline-secondary" href="/admin/settings/dns">Configurações DNS</a>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="card mt-4 mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Notificações Recentes</span>
    <?php if (isset($notifications_unread) && (int)$notifications_unread > 0): ?>
      <span class="badge text-bg-danger">Não lidas: <?= (int)$notifications_unread ?></span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (empty($notifications_recent)): ?>
      <div class="text-muted">Sem notificações</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($notifications_recent as $n): ?>
          <li class="list-group-item d-flex justify-content-between align-items-start">
            <div class="ms-2 me-auto">
              <div class="fw-semibold"><?= htmlspecialchars((string)($n['title'] ?? '')) ?></div>
              <div class="small text-muted" style="max-width: 900px; white-space: pre-wrap;"><?= nl2br(htmlspecialchars((string)($n['message'] ?? ''))) ?></div>
            </div>
            <span class="badge text-bg-secondary"><?= htmlspecialchars((string)($n['type'] ?? '')) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-3">
        <a class="btn btn-sm btn-outline-primary" href="/admin/notifications">Ver todas</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Total de Vendas (período)</div>
        <div class="fs-3 fw-bold"><?= (int)($total_count ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Bruto (USD)</div>
        <div class="fs-4 fw-bold">$ <?= number_format((float)($summary['total_bruto_usd'] ?? 0), 2) ?></div>
        <div class="text-muted">BRL R$ <?= number_format(((float)($summary['total_bruto_usd'] ?? 0)) * (float)($rate ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Líquido (após custos) (USD)</div>
        <div class="fs-4 fw-bold">$ <?= number_format((float)($summary['total_liquido_usd'] ?? 0), 2) ?></div>
        <div class="text-muted">BRL R$ <?= number_format(((float)($summary['total_liquido_usd'] ?? 0)) * (float)($rate ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Comissão (USD)</div>
        <div class="fs-4 fw-bold">$ <?= number_format((float)($commission_total_usd ?? 0), 2) ?></div>
        <div class="text-muted">BRL R$ <?= number_format(((float)($commission_total_usd ?? 0)) * (float)($rate ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
</div>

<?php if ($role === 'admin' && !empty($admin_data)): ?>
  <?php $k = $admin_data['admin_kpis'] ?? []; $c = $admin_data['charts'] ?? []; ?>
  <div class="row g-3 mt-3">
    <div class="col-md-3">
      <div class="card text-bg-light h-100">
        <div class="card-body">
          <div class="fs-6 text-muted">Bruto da Empresa (USD)</div>
          <div class="fs-4 fw-bold">$ <?= number_format((float)($k['team_bruto_total'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light h-100">
        <div class="card-body">
          <div class="fs-6 text-muted">Pedidos da Empresa</div>
          <div class="fs-4 fw-bold"><?= (int)($k['orders_count'] ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light h-100">
        <div class="card-body">
          <div class="fs-6 text-muted">Taxa Global (Imposto)</div>
          <div class="fs-4 fw-bold"><?= number_format(((float)($k['global_cost_rate'] ?? 0))*100, 2) ?>%</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <div class="fs-6 text-muted">Pro-Labore (%)</div>
              <div class="fs-5 fw-bold"><?= number_format((float)($k['prolabore_pct'] ?? 0), 2) ?>%</div>
            </div>
            <div class="text-end">
              <div class="fs-6 text-muted">Pro-Labore (USD)</div>
              <div class="fs-5 fw-bold">$ <?= number_format((float)($k['prolabore_usd'] ?? 0), 2) ?></div>
            </div>
          </div>
          <div class="small text-muted">Caixa Final: USD <?= number_format((float)($k['company_cash_usd'] ?? 0), 2) ?> · BRL R$ <?= number_format((float)($k['company_cash_brl'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-3 mt-2">
    <div class="col-md-3">
      <div class="card text-bg-light h-100">
        <div class="card-body">
          <div class="fs-6 text-muted">Vendedores Ativos</div>
          <div class="fs-4 fw-bold"><?= (int)($k['active_sellers'] ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light h-100">
        <div class="card-body">
          <div class="fs-6 text-muted">Comissões a Pagar (USD)</div>
          <div class="fs-4 fw-bold">$ <?= number_format((float)($k['sum_commissions_usd'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="fs-6 text-muted mb-2">Vendedor com mais vendas (Quantidade)</div>
          <canvas id="chartPie" height="140"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-3 mt-2">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="fs-6 text-muted mb-2">Valor Vendido por Vendedor (USD)</div>
          <canvas id="chartLine" height="180"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="fs-6 text-muted mb-2">Custos da Empresa</div>
          <canvas id="chartBar" height="180"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-3 mt-2">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="fs-6 text-muted mb-2">Vendas x Atendimentos por Vendedor</div>
          <canvas id="chartScatter" height="140"></canvas>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Últimas Vendas (Hoje)</span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Data</th>
            <th>Cliente</th>
            <th>Pedido</th>
            <th>Bruto (USD)</th>
            <th>Líquido (USD)</th>
            <th>Comissão (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_today)): ?>
            <tr><td colspan="6" class="text-center text-muted">Sem vendas</td></tr>
          <?php else: foreach ($recent_today as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['dt'] ?? $r['created_at'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['cliente_nome'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['numero_pedido'] ?? '-') ?></td>
              <td>$ <?= number_format((float)($r['bruto_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($r['liquido_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($r['comissao_usd'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($role === 'admin' && !empty($admin_data)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    (function(){
      var charts = <?= json_encode($admin_data['charts'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
      function byId(id){ return document.getElementById(id); }
      var palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab','#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd'];
      var fmtUSD = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
      if (charts && byId('chartPie')) {
        var pieColors = (charts.pie.labels||[]).map(function(_,i){ return palette[i % palette.length]; });
        new Chart(byId('chartPie'), {
          type: 'pie',
          data: { labels: charts.pie.labels || [], datasets: [{ label: 'Vendas (qtd)', data: charts.pie.data || [], backgroundColor: pieColors, borderColor: '#ffffff', borderWidth: 2 }] },
          options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function(ctx){ var lbl = ctx.label || ''; var v = ctx.parsed || 0; var ds = ctx.dataset || {}; var data = ds.data || []; var total = data.reduce(function(a,b){ return a + (b||0); }, 0) || 1; var pct = ((v/total)*100).toFixed(1)+'%'; return lbl+': '+v+' ('+pct+')'; } } } } }
        });
      }
      if (charts && byId('chartLine')) {
        new Chart(byId('chartLine'), {
          type: 'line',
          data: { labels: charts.line.labels || [], datasets: [{ label: 'Valor Vendido (USD)', data: charts.line.data || [], borderColor: '#4e79a7', backgroundColor: 'rgba(78,121,167,0.15)', tension: 0.25, fill: true, pointRadius: 3, pointHoverRadius: 5 }] },
          options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function(ctx){ var v = ctx.parsed.y || 0; return (ctx.dataset.label? ctx.dataset.label+': ' : '') + fmtUSD.format(v); } } } } }
        });
      }
      if (charts && byId('chartBar')) {
        var barColors = (charts.bar.labels||[]).map(function(_,i){ return palette[i % palette.length]; });
        new Chart(byId('chartBar'), {
          type: 'bar',
          data: { labels: charts.bar.labels || [], datasets: [{ label: 'Custos (USD)', data: charts.bar.data || [], backgroundColor: barColors }] },
          options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function(ctx){ var v = ctx.parsed.y || 0; return (ctx.dataset.label? ctx.dataset.label+': ' : '') + fmtUSD.format(v); } } } } }
        });
      }
      if (charts && byId('chartScatter')) {
        var ds = (charts.scatter || []).map(function(p, i){ return { x: p.x||0, y: p.y||0, label: p.label||'', backgroundColor: palette[i % palette.length] }; });
        new Chart(byId('chartScatter'), {
          type: 'scatter',
          data: { datasets: [{ label: 'Vendedor', data: ds, parsing: false, showLine: false, pointRadius: 4 }] },
          options: { responsive: true, scales: { x: { title: { display: true, text: 'Vendas (Qtd)' } }, y: { title: { display: true, text: 'Atendimentos (Qtd)' } } }, plugins: { tooltip: { callbacks: { label: function(ctx){ var p=ctx.raw||{}; return (p.label? (p.label+': '):'')+ '('+p.x+','+p.y+')'; } } } } }
        });
      }
    })();
  </script>
<?php endif; ?>

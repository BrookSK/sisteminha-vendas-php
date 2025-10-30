<?php
// Simulator view: mirrors admin dashboard layout, plus a form to adjust costs
$role = (string) ((\Core\Auth::user()['role'] ?? 'seller'));
if ($role !== 'admin') { echo '<div class="alert alert-danger">Acesso restrito.</div>'; return; }
$k = $admin_data['admin_kpis'] ?? [];
$c = $admin_data['charts'] ?? [];
$sim = $admin_data['sim'] ?? [];
$rate = (float)($rate ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">Simulador de Custos</h4>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Período: <?= htmlspecialchars($period_from ?? '') ?> a <?= htmlspecialchars($period_to ?? '') ?></span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Bruto da Empresa</div>
        <div class="display-6 fw-bold">$ <?= number_format((float)($k['team_bruto_total'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Pedidos</div>
        <div class="display-6 fw-bold"><?= (int)($k['orders_count'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Impostos (Global)</div>
        <div class="display-6 fw-bold">$ <?= number_format(((float)($k['team_bruto_total'] ?? 0)) * ((float)($k['global_cost_rate'] ?? 0)), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body d-flex flex-column justify-content-between">
        <div>
          <div class="text-muted small">Pro-labore</div>
          <div class="fs-4 fw-bold">$ <?= number_format((float)($k['prolabore_usd'] ?? 0), 2) ?></div>
        </div>
        <div class="mt-2">
          <div class="text-muted small">Caixa</div>
          <div class="fs-5 fw-bold">$ <?= number_format((float)($k['company_cash_usd'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Vendedores Ativos</div>
        <div class="fs-3 fw-bold mb-3"><?= (int)($k['active_sellers'] ?? 0) ?></div>
        <div class="text-muted small">Comissões a Pagar</div>
        <div class="fs-5 fw-bold">$ <?= number_format((float)($k['sum_commissions_usd'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="fs-6 text-muted mb-2">Vendas por Vendedor (Qtd)</div>
        <canvas id="chartPie" height="180"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="fs-6 text-muted mb-2">Valor Vendido por Vendedor (USD)</div>
        <canvas id="chartLine" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fs-6 text-muted">Custos da Empresa (Simulado)</div>
          <small class="text-muted">Somente nesta tela</small>
        </div>
        <canvas id="chartBar" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="fs-6 text-muted mb-2">Custos (Ajuste Rápido)</div>
        <form method="post">
          <div class="mb-2">Custos explícitos</div>
          <!-- Impostos (Global) inline -->
          <div class="row g-2 align-items-end mb-2">
            <div class="col-md-5">
              <label class="form-label">Descrição</label>
              <input type="text" class="form-control" value="Impostos (Global)" disabled>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tipo</label>
              <select class="form-select" disabled>
                <option selected>Percentual (%)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Valor (%)</label>
              <input type="number" step="0.01" class="form-control" name="sim[cost_rate_pct]" value="<?= htmlspecialchars((string)($sim['cost_rate_pct'] ?? 0)) ?>">
            </div>
          </div>
          <!-- Demais custos explícitos do período -->
          <?php $source = $sim['explicit_source'] ?? []; foreach ($source as $i => $row): ?>
            <div class="row g-2 align-items-end mb-2">
              <div class="col-md-5">
                <label class="form-label">Descrição</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars((string)($row['descricao'] ?? ($row['categoria'] ?? 'Custo'))) ?>" disabled>
              </div>
              <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="sim[explicit][<?= $i ?>][valor_tipo]">
                  <?php $t = $row['valor_tipo'] ?? 'fixed'; $ov = $sim['explicit'][$i]['valor_tipo'] ?? null; $sel = $ov ?? $t; ?>
                  <option value="fixed" <?= $sel==='fixed'?'selected':'' ?>>USD Fixo</option>
                  <option value="percent" <?= $sel==='percent'?'selected':'' ?>>Percentual (%)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Valor</label>
                <?php $defaultVal = ($sel==='percent') ? (float)($row['valor_percent'] ?? 0) : (float)($row['valor_usd'] ?? 0); $cur = $sim['explicit'][$i]['valor'] ?? $defaultVal; ?>
                <input type="number" step="0.01" class="form-control" name="sim[explicit][<?= $i ?>][valor]" value="<?= htmlspecialchars((string)$cur) ?>">
              </div>
            </div>
          <?php endforeach; ?>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="?" class="btn btn-outline-secondary">Limpar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  var charts = <?= json_encode($c ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  function byId(id){ return document.getElementById(id); }
  var palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab','#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd'];
  var fmtUSD = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
  if (charts && byId('chartPie')) {
    var colors = (charts.pie.labels || []).map(function(_, i) { return palette[i % palette.length]; });
    new Chart(byId('chartPie'), {
      type: 'pie',
      data: {
        labels: charts.pie.labels || [],
        datasets: [{
          label: 'Pedidos',
          data: charts.pie.data || [],
          backgroundColor: colors,
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                var l = ctx.label || '';
                var v = ctx.parsed || 0;
                var ds = (ctx.dataset && Array.isArray(ctx.dataset.data)) ? ctx.dataset.data : [];
                var total = 0;
                for (var i = 0; i < ds.length; i++) { total += (ds[i] || 0); }
                if (!total) total = 1;
                var pct = ((v / total) * 100).toFixed(1) + '%';
                return l + ': ' + v + ' (' + pct + ')';
              }
            }
          }
        }
      }
    });
  }
  if (charts && byId('chartLine')) {
    new Chart(byId('chartLine'), {
      type: 'line',
      data: {
        labels: charts.line.labels || [],
        datasets: [{
          label: 'Valor Vendido (USD)',
          data: charts.line.data || [],
          borderColor: '#4e79a7',
          backgroundColor: 'rgba(78,121,167,0.15)',
          tension: 0.25,
          fill: true
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) { return fmtUSD.format((ctx.parsed && ctx.parsed.y) || 0); }
            }
          }
        }
      }
    });
  }
  if (charts && byId('chartBar')) {
    var colorsB = (charts.bar.labels||[]).map(function(_,i){ return palette[i % palette.length]; });
    new Chart(byId('chartBar'), { type: 'bar', data: { labels: charts.bar.labels||[], datasets: [{ label:'Custos (USD)', data: charts.bar.data||[], backgroundColor: colorsB }] }, options: { responsive:true, scales:{ y:{ beginAtZero:true } } } });
  }
})();
</script>
